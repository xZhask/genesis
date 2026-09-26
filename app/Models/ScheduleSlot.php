<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Materia que ve una sección en una franja y un día de la semana. */
class ScheduleSlot extends Model
{
    protected $fillable = ['section_id', 'schedule_block_id', 'weekday', 'subject_id'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(ScheduleBlock::class, 'schedule_block_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
