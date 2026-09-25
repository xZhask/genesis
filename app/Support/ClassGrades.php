<?php

namespace App\Support;

use App\Enums\EvaluationComponent;
use App\Enums\Performance;
use App\Models\GradeItem;
use App\Models\GradingScale;
use App\Models\Period;
use App\Models\Score;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Collection;

/**
 * Notas de una clase (sección y materia) en un periodo, calculadas en el
 * servidor:
 * - cada componente (saber, hacer, ser) es el promedio de sus actividades
 *   calificadas;
 * - la nota del periodo es el promedio ponderado de los componentes con
 *   nota (si un componente aún no tiene actividades, no cuenta);
 * - el desempeño sale de la nota redondeada según la escala del año.
 */
class ClassGrades
{
    /** @var Collection<int, GradeItem> */
    public readonly Collection $items;

    /** @var array<int, array<int, float>> [actividad][matrícula] => nota */
    public readonly array $scores;

    public function __construct(
        public readonly Section $section,
        public readonly Subject $subject,
        public readonly Period $period,
        public readonly GradingScale $scale,
    ) {
        $order = array_flip(array_map(fn ($c) => $c->value, EvaluationComponent::cases()));

        $this->items = GradeItem::where('section_id', $section->id)
            ->where('subject_id', $subject->id)
            ->where('period_id', $period->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->sortBy(fn (GradeItem $i) => $order[$i->component->value])
            ->values();

        $scores = [];
        foreach (Score::whereIn('grade_item_id', $this->items->pluck('id'))->get() as $score) {
            $scores[$score->grade_item_id][$score->enrollment_id] = $score->value;
        }
        $this->scores = $scores;
    }

    public function score(int $itemId, int $enrollmentId): ?float
    {
        return $this->scores[$itemId][$enrollmentId] ?? null;
    }

    /**
     * @return array{knowing: ?float, doing: ?float, being: ?float, score: ?float, performance: ?Performance}
     */
    public function forEnrollment(int $enrollmentId): array
    {
        $components = [];
        foreach (EvaluationComponent::cases() as $component) {
            $values = $this->items
                ->filter(fn (GradeItem $i) => $i->component === $component)
                ->map(fn (GradeItem $i) => $this->score($i->id, $enrollmentId))
                ->filter(fn ($v) => $v !== null);
            $components[$component->value] = $values->isEmpty() ? null : $values->avg();
        }

        $score = static::weighted($components, $this->scale);

        return [
            'knowing' => $components['knowing'] === null ? null : $this->scale->round($components['knowing']),
            'doing' => $components['doing'] === null ? null : $this->scale->round($components['doing']),
            'being' => $components['being'] === null ? null : $this->scale->round($components['being']),
            'score' => $score,
            'performance' => $this->scale->performanceFor($score),
        ];
    }

    /**
     * Promedio ponderado de los componentes con nota, redondeado.
     *
     * @param  array<string, ?float>  $components
     */
    public static function weighted(array $components, GradingScale $scale): ?float
    {
        $sum = 0.0;
        $weights = 0.0;
        foreach (EvaluationComponent::cases() as $component) {
            $value = $components[$component->value] ?? null;
            if ($value !== null) {
                $sum += $value * $scale->weight($component);
                $weights += $scale->weight($component);
            }
        }

        return $weights > 0 ? $scale->round($sum / $weights) : null;
    }

    /** Actividades agrupadas por componente, en orden saber, hacer, ser. */
    public function itemsByComponent(): Collection
    {
        return collect(EvaluationComponent::cases())
            ->mapWithKeys(fn ($c) => [$c->value => $this->items->filter(fn (GradeItem $i) => $i->component === $c)->values()]);
    }
}
