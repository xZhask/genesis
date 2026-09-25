<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Evaluación descriptiva de preescolar: texto por dimensión, niño y periodo. */
class DescriptiveEvaluation extends Model
{
    protected $fillable = ['enrollment_id', 'subject_id', 'period_id', 'text', 'recorded_by'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
