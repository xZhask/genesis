<?php

namespace App\Support;

use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\EvaluationComponent;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\GradeItem;
use App\Models\GradingScale;
use App\Models\Period;
use App\Models\PeriodResult;
use App\Models\SchoolYear;
use App\Models\Score;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Alertas para docentes y administración (fase 3). Se calculan al abrir la
 * página, sin guardar nada:
 * - cumpleaños de los próximos días;
 * - bajo rendimiento: nota del periodo en curso por debajo de la aprobatoria
 *   (con un mínimo de actividades calificadas) o un acumulado con el que ya
 *   no se alcanza a aprobar el año;
 * - inasistencia: faltas sin excusa en una materia dentro del periodo.
 *
 * Las familias no ven estas alertas: solo consultan periodos cerrados.
 */
class AcademicAlerts
{
    /** Periodo de referencia: el de hoy o, en vacaciones, el último que empezó. */
    public readonly ?Period $period;

    /** @var Collection<int, Enrollment> matrículas activas de las secciones */
    private Collection $enrollments;

    /** @var Collection<int, Enrollment> */
    private Collection $byId;

    /** @var array<int, int> matrícula => sección */
    private array $sectionOf;

    private Collection $low;

    private Collection $absent;

    /**
     * @param  Collection<int, Section>  $sections
     * @param  array<int, list<int>>|null  $subjects  materias por sección; null = todas
     */
    public function __construct(
        public readonly SchoolYear $year,
        private readonly Collection $sections,
        private readonly ?array $subjects = null,
    ) {
        $this->period = $year->periodFor(today())
            ?? $year->periods->filter(fn (Period $p) => $p->starts_on->lte(today()))->last();

        $this->enrollments = Enrollment::whereIn('section_id', $sections->pluck('id'))
            ->where('status', EnrollmentStatus::Active)
            ->with(['student', 'section.grade'])
            ->get()
            ->sortBy(fn (Enrollment $e) => [$e->section->grade->position, $e->section->name, $e->student->sortName()])
            ->values();
        $this->byId = $this->enrollments->keyBy('id');
        $this->sectionOf = $this->enrollments->pluck('section_id', 'id')->all();
    }

    /** Las clases que dicta el docente. */
    public static function forTeacher(User $teacher, SchoolYear $year): self
    {
        $assignments = $teacher->assignmentsIn($year);

        return new self(
            $year,
            $assignments->pluck('section')->unique('id')->values(),
            $assignments->groupBy('section_id')->map(fn ($a) => $a->pluck('subject_id')->all())->all(),
        );
    }

    /** Todas las materias de las secciones dadas (director de grupo, admin). */
    public static function forSections(SchoolYear $year, Collection $sections): self
    {
        return new self($year, $sections);
    }

    /**
     * @return Collection<int, array{enrollment: Enrollment, date: Carbon, days: int}>
     */
    public function birthdays(): Collection
    {
        $window = (int) config('school.alerts.birthday_days');

        return $this->enrollments
            ->filter(fn (Enrollment $e) => $e->student->birth_date)
            ->map(function (Enrollment $e) {
                $date = self::nextBirthday($e->student->birth_date);

                return ['enrollment' => $e, 'date' => $date, 'days' => (int) today()->diffInDays($date)];
            })
            ->filter(fn ($b) => $b['days'] < $window)
            ->sortBy(fn ($b) => [$b['days'], $b['enrollment']->student->sortName()])
            ->values();
    }

    /** Próximo cumpleaños desde hoy (el 29 de febrero se celebra el 28 en años no bisiestos). */
    public static function nextBirthday(Carbon $birthDate): Carbon
    {
        $date = self::birthdayIn($birthDate, today()->year);

        return $date->lt(today()) ? self::birthdayIn($birthDate, today()->year + 1) : $date;
    }

    private static function birthdayIn(Carbon $birthDate, int $year): Carbon
    {
        $day = $birthDate->month === 2 && $birthDate->day === 29 && ! Carbon::create($year)->isLeapYear() ? 28 : $birthDate->day;

        return Carbon::create($year, $birthDate->month, $day)->startOfDay();
    }

    /**
     * Estudiantes que necesitan acompañamiento en una materia.
     *
     * @return Collection<int, array{enrollment: Enrollment, subject: Subject, score: ?float, year: bool}>
     */
    public function lowPerformance(): Collection
    {
        if (isset($this->low)) {
            return $this->low;
        }
        $numeric = $this->enrollments->reject(fn (Enrollment $e) => $e->section->grade->isPreschool());
        if (! $this->period || $numeric->isEmpty()) {
            return collect();
        }

        $scale = $this->scale();
        $sectionIds = $numeric->pluck('section_id')->unique();
        $alerts = [];

        // Periodo en curso: misma cuenta que la planilla (ClassGrades)
        $items = GradeItem::where('period_id', $this->period->id)->whereIn('section_id', $sectionIds)->get()
            ->filter(fn (GradeItem $i) => $this->includes($i->section_id, $i->subject_id));
        $scores = Score::whereIn('grade_item_id', $items->pluck('id'))->whereIn('enrollment_id', $numeric->pluck('id'))->get()
            ->groupBy('enrollment_id');
        $itemsById = $items->keyBy('id');
        $min = (int) config('school.alerts.min_graded_items');

        foreach ($scores as $enrollmentId => $mine) {
            foreach ($mine->groupBy(fn (Score $s) => $itemsById[$s->grade_item_id]->subject_id) as $subjectId => $subjectScores) {
                if ($subjectScores->count() < $min) {
                    continue;
                }
                $components = [];
                foreach (EvaluationComponent::cases() as $component) {
                    $values = $subjectScores->filter(fn (Score $s) => $itemsById[$s->grade_item_id]->component === $component);
                    $components[$component->value] = $values->isEmpty() ? null : $values->avg('value');
                }
                $score = ClassGrades::weighted($components, $scale);
                if ($score !== null && $score < $scale->passing_score) {
                    $alerts["{$enrollmentId}|{$subjectId}"] = ['score' => $score, 'year' => false];
                }
            }
        }

        // Año: con lo acumulado en los periodos cerrados ya no alcanza a aprobar
        $closed = $this->year->periods->filter->isClosed();
        if ($closed->isNotEmpty()) {
            $remaining = $this->year->periods->reject->isClosed()->sum(fn (Period $p) => (float) $p->weight);
            $weights = $closed->mapWithKeys(fn (Period $p) => [$p->id => (float) $p->weight]);
            $results = PeriodResult::whereIn('period_id', $closed->pluck('id'))
                ->whereIn('enrollment_id', $numeric->pluck('id'))
                ->whereNotNull('score')
                ->get()
                ->filter(fn (PeriodResult $r) => $this->includes($this->sectionOf[$r->enrollment_id], $r->subject_id))
                ->groupBy(fn (PeriodResult $r) => "{$r->enrollment_id}|{$r->subject_id}");

            foreach ($results as $key => $periodResults) {
                $accumulated = $scale->round($periodResults->sum(fn ($r) => $r->score * $weights[$r->period_id] / 100));
                if (in_array($scale->yearStatus($accumulated, $remaining)['key'], ['support', 'not_reached'], true)) {
                    $alerts[$key] = ['score' => $alerts[$key]['score'] ?? null, 'year' => true];
                }
            }
        }

        return $this->low = $this->rows($alerts);
    }

    /**
     * Faltas sin excusa en una materia dentro del periodo, desde el umbral configurado.
     *
     * @return Collection<int, array{enrollment: Enrollment, subject: Subject, count: int}>
     */
    public function absences(): Collection
    {
        if (isset($this->absent)) {
            return $this->absent;
        }
        if (! $this->period) {
            return collect();
        }

        $counts = Attendance::where('period_id', $this->period->id)
            ->where('status', AttendanceStatus::Absent)
            ->whereIn('enrollment_id', $this->enrollments->pluck('id'))
            ->selectRaw('enrollment_id, subject_id, count(*) as total')
            ->groupBy('enrollment_id', 'subject_id')
            ->havingRaw('count(*) >= ?', [(int) config('school.alerts.absences')])
            ->get();

        $alerts = [];
        foreach ($counts as $row) {
            if ($this->includes($this->sectionOf[$row->enrollment_id], $row->subject_id)) {
                $alerts["{$row->enrollment_id}|{$row->subject_id}"] = ['count' => (int) $row->total];
            }
        }

        return $this->absent = $this->rows($alerts)->sortByDesc('count')->values();
    }

    public function scale(): GradingScale
    {
        return GradingScale::forYear($this->year);
    }

    /** Preescolar no tiene notas: sin secciones numéricas no hay alerta de rendimiento. */
    public function hasNumericGrades(): bool
    {
        return $this->sections->contains(fn (Section $s) => ! $s->grade->isPreschool());
    }

    public function isEmpty(): bool
    {
        return $this->birthdays()->isEmpty() && $this->lowPerformance()->isEmpty() && $this->absences()->isEmpty();
    }

    /** Estudiantes distintos con alguna alerta de rendimiento o inasistencia. */
    public function studentCount(): int
    {
        return $this->lowPerformance()->concat($this->absences())->pluck('enrollment.id')->unique()->count();
    }

    private function includes(int $sectionId, int $subjectId): bool
    {
        return $this->subjects === null || in_array($subjectId, $this->subjects[$sectionId] ?? [], true);
    }

    /**
     * «matrícula|materia» => datos → filas con la matrícula y la materia, en el orden de las matrículas.
     */
    private function rows(array $alerts): Collection
    {
        if (! $alerts) {
            return collect();
        }

        $subjects = Subject::whereIn('id', collect(array_keys($alerts))->map(fn ($k) => (int) explode('|', $k)[1])->unique())->get()->keyBy('id');
        $order = $this->enrollments->pluck('id')->flip();

        return collect($alerts)
            ->map(function ($alert, $key) use ($subjects) {
                [$enrollmentId, $subjectId] = array_map('intval', explode('|', $key));

                return ['enrollment' => $this->byId[$enrollmentId], 'subject' => $subjects[$subjectId]] + $alert;
            })
            ->sortBy(fn ($row) => [$order[$row['enrollment']->id], $row['subject']->position ?? 0, $row['subject']->name])
            ->values();
    }
}
