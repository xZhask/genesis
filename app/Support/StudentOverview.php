<?php

namespace App\Support;

use App\Enums\AttendanceStatus;
use App\Enums\EvaluationComponent;
use App\Models\Attendance;
use App\Models\DescriptiveEvaluation;
use App\Models\Enrollment;
use App\Models\GradingScale;
use App\Models\PeriodObjective;
use App\Models\PeriodReport;
use App\Models\PeriodResult;
use App\Models\SchoolYear;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Lo que ven el acudiente y el estudiante en el portal: sección, materias
 * con su docente y asistencia del año (faltas por materia y periodo, como
 * la columna «Inas» del boletín).
 */
class StudentOverview
{
    public function __construct(public readonly Student $student, public readonly ?SchoolYear $year) {}

    public function enrollment(): ?Enrollment
    {
        return once(fn () => $this->year
            ? $this->student->enrollments()->where('school_year_id', $this->year->id)
                ->with(['section.grade', 'section.homeroomTeacher', 'section.assignments.teacher'])->first()
            : null);
    }

    public function scale(): ?GradingScale
    {
        return $this->year ? once(fn () => GradingScale::forYear($this->year)) : null;
    }

    /** Las familias ven solo las notas de periodos cerrados (decisión del 25/09/2026). */
    public function closedPeriods(): Collection
    {
        return $this->year ? $this->year->periods->filter->isClosed()->values() : collect();
    }

    public function hasNumericGrades(): bool
    {
        return (bool) $this->enrollment() && ! $this->enrollment()->section->grade->isPreschool();
    }

    /**
     * Notas por materia de los periodos cerrados, acumulado y lo que falta
     * para aprobar, con los logros del último periodo cerrado.
     *
     * @return Collection<int, array{subject: string, teacher: ?string, periods: array<int, ?PeriodResult>, accumulated: ?float, status: array{key: string, needed: ?float}, objectives: list<string>, absences: int}>
     */
    public function grades(): Collection
    {
        $enrollment = $this->enrollment();
        if (! $enrollment || ! $this->hasNumericGrades() || $this->closedPeriods()->isEmpty()) {
            return collect();
        }

        $scale = $this->scale();
        $closed = $this->closedPeriods();
        $latest = $closed->last();
        $results = PeriodResult::where('enrollment_id', $enrollment->id)
            ->whereIn('period_id', $closed->pluck('id'))
            ->get()
            ->groupBy('subject_id');
        $objectives = PeriodObjective::where('section_id', $enrollment->section_id)
            ->where('period_id', $latest->id)
            ->get()
            ->groupBy('subject_id');
        $teachers = $enrollment->section->assignments->mapWithKeys(fn ($a) => [$a->subject_id => $a->teacher->name]);
        $remaining = $this->year->periods->reject->isClosed()->sum(fn ($p) => (float) $p->weight);

        return $enrollment->section->grade->subjects()->get()->map(function ($subject) use ($results, $objectives, $teachers, $closed, $latest, $scale, $remaining) {
            $mine = ($results[$subject->id] ?? collect())->keyBy('period_id');
            $periods = $this->year->periods->mapWithKeys(fn ($p) => [$p->number => $p->isClosed() ? $mine->get($p->id) : null])->all();

            $scored = $closed->filter(fn ($p) => $mine->get($p->id)?->score !== null);
            $accumulated = $scored->isEmpty() ? null
                : $scale->round($scored->sum(fn ($p) => $mine->get($p->id)->score * (float) $p->weight / 100));

            // Logros del último periodo cerrado, con la frase según el desempeño de cada componente
            $latestResult = $mine->get($latest->id);
            $texts = [];
            foreach (($objectives[$subject->id] ?? collect())->sortBy(fn ($o) => array_search($o->component, EvaluationComponent::cases(), true)) as $objective) {
                $performance = $scale->performanceFor($latestResult?->{$objective->component->value});
                if ($performance) {
                    $texts[] = $scale->objectiveText($performance, $objective->text);
                }
            }

            return [
                'subject' => $subject->name,
                'teacher' => $teachers[$subject->id] ?? null,
                'periods' => $periods,
                'accumulated' => $accumulated,
                'status' => $scale->yearStatus($accumulated, $remaining),
                'objectives' => $texts,
            ];
        });
    }

    /**
     * Preescolar: descripciones por dimensión del último periodo cerrado.
     *
     * @return Collection<int, array{name: string, text: ?string}>
     */
    public function descriptions(): Collection
    {
        $enrollment = $this->enrollment();
        $latest = $this->closedPeriods()->last();
        if (! $enrollment || ! $latest || $this->hasNumericGrades()) {
            return collect();
        }

        $texts = DescriptiveEvaluation::where('enrollment_id', $enrollment->id)->where('period_id', $latest->id)->pluck('text', 'subject_id');

        return $enrollment->section->grade->subjects()->get()
            ->map(fn ($s) => ['name' => $s->name, 'text' => $texts[$s->id] ?? null]);
    }

    /** Comportamiento y observaciones del director de grupo en los periodos cerrados. */
    public function behavior(): Collection
    {
        $enrollment = $this->enrollment();
        if (! $enrollment || $this->closedPeriods()->isEmpty()) {
            return collect();
        }

        return PeriodReport::where('enrollment_id', $enrollment->id)
            ->whereIn('period_id', $this->closedPeriods()->pluck('id'))
            ->get()
            ->keyBy('period_id');
    }

    /**
     * Materias del plan de estudios del grado con su docente e inasistencias.
     *
     * @return Collection<int, array{subject: string, area: string, teacher: ?string, absences: array<int, int>, total: int, late: int}>
     */
    public function subjects(): Collection
    {
        $enrollment = $this->enrollment();
        if (! $enrollment) {
            return collect();
        }

        $section = $enrollment->section;
        $teachers = $section->assignments->mapWithKeys(fn ($a) => [$a->subject_id => $a->teacher->name]);
        $counts = $this->counts();
        $periods = $this->year->periods;

        return $section->grade->subjects()->with('area')->get()->map(function ($subject) use ($teachers, $counts, $periods) {
            $mine = $counts->where('subject_id', $subject->id);
            $absences = $periods->mapWithKeys(fn ($p) => [$p->number => (int) $mine
                ->where('period_id', $p->id)->where('status', AttendanceStatus::Absent)->sum('total')]);

            return [
                'subject' => $subject->name,
                'area' => $subject->area->name,
                'teacher' => $teachers[$subject->id] ?? null,
                'absences' => $absences->all(),
                'total' => $absences->sum(),
                'late' => (int) $mine->where('status', AttendanceStatus::Late)->sum('total'),
            ];
        });
    }

    /** Totales del año: faltas, llegadas tarde y excusas. */
    public function totals(): array
    {
        $counts = $this->counts();

        return collect(AttendanceStatus::cases())
            ->mapWithKeys(fn ($s) => [$s->value => (int) $counts->where('status', $s)->sum('total')])
            ->all();
    }

    /** Últimas faltas, llegadas tarde y excusas (lo que el acudiente quiere revisar). */
    public function recentIssues(int $limit = 15): Collection
    {
        $enrollment = $this->enrollment();
        if (! $enrollment) {
            return collect();
        }

        return Attendance::where('enrollment_id', $enrollment->id)
            ->where('status', '!=', AttendanceStatus::Present)
            ->with('subject')
            ->orderByDesc('date')
            ->limit($limit)
            ->get();
    }

    private function counts(): Collection
    {
        return once(fn () => $this->enrollment()
            ? Attendance::where('enrollment_id', $this->enrollment()->id)
                ->selectRaw('subject_id, period_id, status, count(*) as total')
                ->groupBy('subject_id', 'period_id', 'status')
                ->get()
            : collect());
    }
}
