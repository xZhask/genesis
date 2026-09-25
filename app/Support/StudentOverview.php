<?php

namespace App\Support;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Enrollment;
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
