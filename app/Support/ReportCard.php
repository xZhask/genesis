<?php

namespace App\Support;

use App\Enums\AttendanceStatus;
use App\Enums\EvaluationComponent;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\GradingScale;
use App\Models\Period;
use App\Models\PeriodObjective;
use App\Models\PeriodReport;
use App\Models\PeriodResult;
use Illuminate\Support\Collection;

/**
 * Datos del boletín de un estudiante en un periodo cerrado (primaria y
 * secundaria). Salen de las notas congeladas al cerrar (period_results):
 * el PDF no cambia aunque después se edite algo.
 *
 * Estructura del boletín actual del colegio: áreas con su IH y su nota
 * (promedio simple de sus materias), y por materia IH, desempeño, faltas,
 * notas de los periodos, acumulado y los tres logros con su frase.
 */
class ReportCard
{
    public readonly GradingScale $scale;

    /** @var Collection<int, Period> periodos del año hasta el del boletín */
    public readonly Collection $periods;

    public function __construct(public readonly Enrollment $enrollment, public readonly Period $period)
    {
        $enrollment->loadMissing(['student', 'section.grade', 'section.homeroomTeacher', 'section.assignments.teacher', 'schoolYear.periods']);
        $this->scale = GradingScale::forYear($enrollment->schoolYear);
        $this->periods = $enrollment->schoolYear->periods->filter(fn (Period $p) => $p->number <= $period->number)->values();
    }

    /** Informe final: el del último periodo del año. */
    public function isFinal(): bool
    {
        return $this->period->number === $this->enrollment->schoolYear->periods->max('number');
    }

    /**
     * @return Collection<int, array{name: string, hours: int, score: ?float, subjects: Collection}>
     */
    public function areas(): Collection
    {
        return once(function () {
            $results = PeriodResult::where('enrollment_id', $this->enrollment->id)
                ->whereIn('period_id', $this->periods->pluck('id'))
                ->get()
                ->groupBy('subject_id');
            $objectives = PeriodObjective::where('section_id', $this->enrollment->section_id)
                ->where('period_id', $this->period->id)
                ->get()
                ->groupBy('subject_id');
            $teachers = $this->enrollment->section->assignments->mapWithKeys(fn ($a) => [$a->subject_id => $a->teacher->name]);
            // Peso de los periodos que faltan después del boletín (para «Necesita»)
            $remaining = $this->enrollment->schoolYear->periods->filter(fn ($p) => $p->number > $this->period->number)->sum(fn ($p) => (float) $p->weight);

            $subjects = $this->enrollment->section->grade->subjects()->with('area')->get()
                ->map(function ($subject) use ($results, $objectives, $teachers, $remaining) {
                    $mine = ($results[$subject->id] ?? collect())->keyBy('period_id');
                    $current = $mine->get($this->period->id);
                    $scores = $this->periods->mapWithKeys(fn ($p) => [$p->number => $mine->get($p->id)?->score])->all();
                    $scored = $this->periods->filter(fn ($p) => $mine->get($p->id)?->score !== null);
                    $accumulated = $scored->isEmpty() ? null
                        : $this->scale->round($scored->sum(fn ($p) => $mine->get($p->id)->score * (float) $p->weight / 100));

                    $texts = [];
                    foreach (EvaluationComponent::cases() as $component) {
                        $objective = ($objectives[$subject->id] ?? collect())->first(fn ($o) => $o->component === $component);
                        $performance = $this->scale->performanceFor($current?->{$component->value});
                        if ($objective && $performance) {
                            $texts[] = $this->scale->objectiveText($performance, $objective->text);
                        }
                    }

                    return [
                        'area' => $subject->area,
                        'name' => $subject->name,
                        'teacher' => $teachers[$subject->id] ?? null,
                        'hours' => (int) $subject->pivot->weekly_hours,
                        'score' => $current?->score,
                        'performance' => $current?->performance,
                        'absences' => (int) ($current?->absences ?? 0),
                        'scores' => $scores,
                        'accumulated' => $accumulated,
                        'status' => $this->scale->yearStatus($accumulated, $remaining),
                        'objectives' => $texts,
                    ];
                });

            return $subjects->groupBy(fn ($s) => $s['area']->id)
                ->map(function (Collection $items) {
                    $scored = $items->pluck('score')->filter(fn ($v) => $v !== null);

                    return [
                        'name' => $items->first()['area']->name,
                        'position' => $items->first()['area']->position,
                        'hours' => $items->sum('hours'),
                        'score' => $scored->isEmpty() ? null : $this->scale->round($scored->avg()),
                        'subjects' => $items->values(),
                    ];
                })
                ->sortBy(fn ($a) => [$a['position'], $a['name']])
                ->values();
        });
    }

    /** Promedio del periodo: promedio simple de las materias con nota. */
    public function average(): ?float
    {
        $scores = $this->areas()->flatMap(fn ($a) => $a['subjects'])->pluck('score')->filter(fn ($v) => $v !== null);

        return $scores->isEmpty() ? null : $this->scale->round($scores->avg());
    }

    public function report(): ?PeriodReport
    {
        return once(fn () => PeriodReport::where('enrollment_id', $this->enrollment->id)->where('period_id', $this->period->id)->first());
    }

    /** Faltas, llegadas tarde y excusas del periodo (todas las materias). */
    public function attendance(): array
    {
        $counts = Attendance::where('enrollment_id', $this->enrollment->id)
            ->where('period_id', $this->period->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(AttendanceStatus::cases())->mapWithKeys(fn ($s) => [$s->value => (int) ($counts[$s->value] ?? 0)])->all();
    }
}
