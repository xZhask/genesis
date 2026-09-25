<?php

namespace App\Models;

use App\Enums\EvaluationComponent;
use App\Enums\Performance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Escala de valoración de un año lectivo (SIEE del colegio): rango de notas,
 * nota aprobatoria, límites de cada desempeño, frase de los logros y pesos
 * de saber, hacer y ser. Se crea con los valores de config/school.php.
 */
class GradingScale extends Model
{
    protected $fillable = [
        'school_year_id', 'min_score', 'max_score', 'passing_score', 'decimals',
        'basic_from', 'high_from', 'superior_from', 'phrases',
        'weight_knowing', 'weight_doing', 'weight_being',
    ];

    protected function casts(): array
    {
        return [
            'min_score' => 'float',
            'max_score' => 'float',
            'passing_score' => 'float',
            'decimals' => 'integer',
            'basic_from' => 'float',
            'high_from' => 'float',
            'superior_from' => 'float',
            'phrases' => 'array',
            'weight_knowing' => 'float',
            'weight_doing' => 'float',
            'weight_being' => 'float',
        ];
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /** La escala del año; si no existe, se crea con los valores por defecto. */
    public static function forYear(SchoolYear $year): self
    {
        return static::firstOrCreate(['school_year_id' => $year->id], static::defaults());
    }

    public static function defaults(): array
    {
        $config = config('school.academic.grading');

        return [
            'min_score' => $config['min_score'],
            'max_score' => $config['max_score'],
            'passing_score' => $config['passing_score'],
            'decimals' => $config['decimals'],
            'basic_from' => $config['basic_from'],
            'high_from' => $config['high_from'],
            'superior_from' => $config['superior_from'],
            'phrases' => $config['phrases'],
            'weight_knowing' => $config['weights']['knowing'],
            'weight_doing' => $config['weights']['doing'],
            'weight_being' => $config['weights']['being'],
        ];
    }

    public function round(float $value): float
    {
        return round($value, $this->decimals);
    }

    public function performanceFor(?float $score): ?Performance
    {
        if ($score === null) {
            return null;
        }

        $score = $this->round($score);

        return match (true) {
            $score >= $this->superior_from => Performance::Superior,
            $score >= $this->high_from => Performance::High,
            $score >= $this->basic_from => Performance::Basic,
            default => Performance::Low,
        };
    }

    public function weight(EvaluationComponent $component): float
    {
        return $this->{'weight_'.$component->value};
    }

    public function phrase(Performance $performance): string
    {
        return $this->phrases[$performance->value] ?? '';
    }

    /** Frase del desempeño + logro en infinitivo: «Soy capaz de identificar…». */
    public function objectiveText(Performance $performance, string $objective): string
    {
        return trim($this->phrase($performance).' '.lcfirst(trim($objective)));
    }

    /** Coma decimal, como en Colombia: 3,50. */
    public function format(?float $score): string
    {
        return $score === null ? '—' : number_format($score, $this->decimals, ',', '.');
    }
}
