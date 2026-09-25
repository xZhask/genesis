<?php

namespace App\Models;

use App\Enums\EvaluationComponent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Actividad evaluada (taller, evaluación, exposición…) de una clase en un periodo. */
class GradeItem extends Model
{
    protected $fillable = ['section_id', 'subject_id', 'period_id', 'component', 'name', 'position', 'created_by'];

    protected function casts(): array
    {
        return ['component' => EvaluationComponent::class];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}
