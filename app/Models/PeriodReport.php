<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Comportamiento y observaciones de un estudiante en el periodo (director de grupo). */
class PeriodReport extends Model
{
    protected $fillable = ['enrollment_id', 'period_id', 'behavior', 'observations', 'recorded_by'];

    protected function casts(): array
    {
        return ['behavior' => 'float'];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
