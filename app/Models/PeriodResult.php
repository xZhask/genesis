<?php

namespace App\Models;

use App\Enums\Performance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Nota de una materia en un periodo, congelada al cerrarlo. */
class PeriodResult extends Model
{
    protected $fillable = ['enrollment_id', 'subject_id', 'period_id', 'knowing', 'doing', 'being', 'score', 'performance', 'absences'];

    protected function casts(): array
    {
        return [
            'knowing' => 'float',
            'doing' => 'float',
            'being' => 'float',
            'score' => 'float',
            'performance' => Performance::class,
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }
}
