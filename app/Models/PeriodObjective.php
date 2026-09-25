<?php

namespace App\Models;

use App\Enums\EvaluationComponent;
use Illuminate\Database\Eloquent\Model;

/** Logro de una clase en un periodo, escrito en infinitivo («identificar las partes del cuento»). */
class PeriodObjective extends Model
{
    protected $fillable = ['section_id', 'subject_id', 'period_id', 'component', 'text', 'updated_by'];

    protected function casts(): array
    {
        return ['component' => EvaluationComponent::class];
    }
}
