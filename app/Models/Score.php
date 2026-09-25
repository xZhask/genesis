<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    protected $fillable = ['grade_item_id', 'enrollment_id', 'value', 'recorded_by'];

    protected function casts(): array
    {
        return ['value' => 'float'];
    }

    public function gradeItem(): BelongsTo
    {
        return $this->belongsTo(GradeItem::class);
    }
}
