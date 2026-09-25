<?php

namespace App\Support;

use App\Enums\EnrollmentStatus;
use App\Enums\EvaluationComponent;
use App\Models\GradeItem;
use App\Models\Period;
use App\Models\PeriodObjective;
use App\Models\PeriodReport;
use App\Models\Score;
use App\Models\Section;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Avance de notas de un periodo, para revisar antes de cerrarlo. Una clase
 * está completa cuando tiene actividades en saber, hacer y ser, todos sus
 * estudiantes activos tienen nota en todas ellas y los tres logros están
 * escritos. Preescolar no se incluye (evaluación descriptiva).
 */
class GradingProgress
{
    /**
     * @return Collection<int, array{section: Section, students: int, behavior: int, classes: Collection}>
     */
    public static function forPeriod(Period $period): Collection
    {
        $sections = Section::where('school_year_id', $period->school_year_id)
            ->with(['grade.subjects', 'assignments.teacher', 'homeroomTeacher'])
            ->withCount(['enrollments as students' => fn ($q) => $q->where('status', EnrollmentStatus::Active)])
            ->get()
            ->reject(fn (Section $s) => $s->grade->isPreschool())
            ->sortBy(fn (Section $s) => [$s->grade->position, $s->name])
            ->values();

        $items = GradeItem::where('period_id', $period->id)->get()->groupBy(fn ($i) => $i->section_id.'|'.$i->subject_id);

        // Notas de estudiantes activos por actividad
        $filled = Score::query()
            ->join('enrollments', 'enrollments.id', '=', 'scores.enrollment_id')
            ->where('enrollments.status', EnrollmentStatus::Active->value)
            ->whereIn('scores.grade_item_id', $items->flatten()->pluck('id'))
            ->groupBy('scores.grade_item_id')
            ->pluck(DB::raw('count(*)'), 'scores.grade_item_id');

        $objectives = PeriodObjective::where('period_id', $period->id)
            ->selectRaw('section_id, subject_id, count(*) as total')
            ->groupBy('section_id', 'subject_id')
            ->get()
            ->keyBy(fn ($o) => $o->section_id.'|'.$o->subject_id);

        $behavior = PeriodReport::query()
            ->join('enrollments', 'enrollments.id', '=', 'period_reports.enrollment_id')
            ->where('period_reports.period_id', $period->id)
            ->where('enrollments.status', EnrollmentStatus::Active->value)
            ->whereNotNull('period_reports.behavior')
            ->groupBy('enrollments.section_id')
            ->pluck(DB::raw('count(*)'), 'enrollments.section_id');

        return $sections->map(function (Section $section) use ($items, $filled, $objectives, $behavior) {
            $teachers = $section->assignments->mapWithKeys(fn ($a) => [$a->subject_id => $a->teacher->name]);

            $classes = $section->grade->subjects->map(function ($subject) use ($section, $items, $filled, $objectives, $teachers) {
                $key = $section->id.'|'.$subject->id;
                $classItems = $items[$key] ?? collect();
                $components = collect(EvaluationComponent::cases())
                    ->mapWithKeys(fn ($c) => [$c->value => $classItems->contains(fn ($i) => $i->component === $c)]);
                $expected = $classItems->count() * $section->students;
                $scores = $classItems->sum(fn ($i) => (int) ($filled[$i->id] ?? 0));
                $objectiveCount = (int) ($objectives[$key]->total ?? 0);

                return [
                    'subject' => $subject->name,
                    'teacher' => $teachers[$subject->id] ?? null,
                    'components' => $components,
                    'items' => $classItems->count(),
                    'scores' => $scores,
                    'expected' => $expected,
                    'objectives' => $objectiveCount,
                    'complete' => $components->every(fn ($has) => $has) && $scores >= $expected && $objectiveCount >= 3,
                ];
            });

            return [
                'section' => $section,
                'students' => $section->students,
                'behavior' => (int) ($behavior[$section->id] ?? 0),
                'classes' => $classes,
            ];
        });
    }
}
