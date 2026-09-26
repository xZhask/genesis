<?php

namespace App\Support;

use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Attendance;
use App\Models\GradingScale;
use App\Models\Period;
use App\Models\PeriodResult;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\TeacherAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Cifras para los gráficos del portal docente y del admin (fase 3).
 * «Asistencia» = presentes + llegadas tarde sobre el total de registros;
 * las faltas con excusa cuentan como falta en el porcentaje.
 */
class Indicators
{
    /** Meses del año lectivo hasta hoy (para el filtro), el más reciente primero. */
    public static function months(SchoolYear $year): Collection
    {
        $months = collect();
        $end = min(today()->startOfMonth(), $year->ends_on->copy()->startOfMonth());
        for ($month = $year->starts_on->copy()->startOfMonth(); $month->lte($end); $month->addMonth()) {
            $months->prepend($month->copy());
        }

        return $months;
    }

    /** Mes elegido en «?mes=2026-09», o el más reciente. */
    public static function month(SchoolYear $year, ?string $value): Carbon
    {
        $months = self::months($year);

        return $months->first(fn (Carbon $m) => $m->format('Y-m') === $value) ?? $months->first() ?? today()->startOfMonth();
    }

    /**
     * Asistencia por día del mes en las clases dadas.
     *
     * @param  Collection<int, TeacherAssignment>  $classes
     * @return Collection<int, array{date: Carbon, total: int, attended: int, percent: float}>
     */
    public static function attendanceByDay(Collection $classes, Carbon $month): Collection
    {
        if ($classes->isEmpty()) {
            return collect();
        }

        return self::attendanceQuery($month)
            ->where(function (Builder $q) use ($classes) {
                foreach ($classes as $class) {
                    $q->orWhere(fn (Builder $pair) => $pair->where('enrollments.section_id', $class->section_id)->where('attendances.subject_id', $class->subject_id));
                }
            })
            ->groupBy('attendances.date')
            ->orderBy('attendances.date')
            ->selectRaw('attendances.date as day')
            ->get()
            ->map(fn ($row) => self::row($row) + ['date' => Carbon::parse($row->day)]);
    }

    /**
     * Asistencia del mes por sección (todas las materias).
     *
     * @return Collection<int, array{section: Section, total: int, attended: int, percent: float}>
     */
    public static function attendanceBySection(SchoolYear $year, Carbon $month): Collection
    {
        $rows = self::attendanceQuery($month)
            ->where('enrollments.school_year_id', $year->id)
            ->groupBy('enrollments.section_id')
            ->selectRaw('enrollments.section_id as section_id')
            ->get()
            ->keyBy('section_id');

        return $year->sections()->with('grade')->get()
            ->sortBy(fn (Section $s) => [$s->grade->position, $s->name])
            ->filter(fn (Section $s) => $rows->has($s->id))
            ->map(fn (Section $s) => self::row($rows[$s->id]) + ['section' => $s])
            ->values();
    }

    /** Total del mes a partir de las filas por día o por sección. */
    public static function total(Collection $rows): ?float
    {
        $total = $rows->sum('total');

        return $total ? round($rows->sum('attended') * 100 / $total, 1) : null;
    }

    /**
     * Aprobados, no aprobados y sin nota por clase en el periodo (nota en vivo).
     *
     * @param  Collection<int, TeacherAssignment>  $classes
     * @return Collection<int, array{class: TeacherAssignment, passed: int, failed: int, pending: int}>
     */
    public static function passFailByClass(Collection $classes, Period $period): Collection
    {
        $scale = GradingScale::forYear($period->schoolYear);

        return $classes
            ->reject(fn (TeacherAssignment $a) => $a->section->grade->isPreschool())
            ->map(function (TeacherAssignment $class) use ($period, $scale) {
                $grades = new ClassGrades($class->section, $class->subject, $period, $scale);
                $counts = ['passed' => 0, 'failed' => 0, 'pending' => 0];
                foreach ($class->section->enrollments()->where('status', EnrollmentStatus::Active)->pluck('id') as $enrollmentId) {
                    $score = $grades->forEnrollment($enrollmentId)['score'];
                    $counts[match (true) {
                        $score === null => 'pending',
                        $score >= $scale->passing_score => 'passed',
                        default => 'failed',
                    }]++;
                }

                return ['class' => $class] + $counts;
            })
            ->values();
    }

    /**
     * Porcentaje de notas de materia aprobadas por grado en cada periodo
     * cerrado (de las notas congeladas al cerrar).
     *
     * @return array{periods: Collection<int, Period>, rows: Collection<int, array{grade: string, cells: array<int, ?array{percent: float, total: int}>}>}
     */
    public static function approvalByGrade(SchoolYear $year): array
    {
        $scale = GradingScale::forYear($year);
        $periods = $year->periods->filter->isClosed()->values();

        $counts = PeriodResult::query()
            ->join('enrollments', 'enrollments.id', '=', 'period_results.enrollment_id')
            ->join('sections', 'sections.id', '=', 'enrollments.section_id')
            ->join('grades', 'grades.id', '=', 'sections.grade_id')
            ->whereIn('period_results.period_id', $periods->pluck('id'))
            ->whereNotNull('period_results.score')
            ->groupBy('grades.id', 'grades.name', 'grades.position', 'period_results.period_id')
            ->orderBy('grades.position')
            ->select('grades.name as grade', 'period_results.period_id')
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when period_results.score >= ? then 1 else 0 end) as passed', [$scale->passing_score])
            ->get();

        $rows = $counts->groupBy('grade')->map(fn ($group, $grade) => [
            'grade' => $grade,
            'cells' => $periods->mapWithKeys(function (Period $p) use ($group) {
                $cell = $group->firstWhere('period_id', $p->id);

                return [$p->id => $cell ? ['percent' => round($cell->passed * 100 / $cell->total, 1), 'total' => (int) $cell->total] : null];
            })->all(),
        ])->values();

        return ['periods' => $periods, 'rows' => $rows];
    }

    /** Paso de la escala de color (1 a 5) para un porcentaje de aprobación. */
    public static function step(float $percent): int
    {
        return match (true) {
            $percent >= 95 => 5,
            $percent >= 90 => 4,
            $percent >= 80 => 3,
            $percent >= 60 => 2,
            default => 1,
        };
    }

    /** Formato colombiano: «94,5 %». */
    public static function percent(?float $value): string
    {
        return $value === null ? '—' : number_format($value, $value == floor($value) ? 0 : 1, ',', '.').' %';
    }

    private static function attendanceQuery(Carbon $month): Builder
    {
        return Attendance::query()
            ->join('enrollments', 'enrollments.id', '=', 'attendances.enrollment_id')
            ->whereBetween('attendances.date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when attendances.status in (?, ?) then 1 else 0 end) as attended', [AttendanceStatus::Present->value, AttendanceStatus::Late->value]);
    }

    private static function row(object $row): array
    {
        $total = (int) $row->total;
        $attended = (int) $row->attended;

        return ['total' => $total, 'attended' => $attended, 'percent' => $total ? round($attended * 100 / $total, 1) : 0.0];
    }
}
