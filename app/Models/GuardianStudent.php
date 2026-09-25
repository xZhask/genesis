<?php

namespace App\Models;

use App\Enums\GuardianRelationship;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** Vínculo acudiente–estudiante con su parentesco. */
class GuardianStudent extends Pivot
{
    protected $table = 'guardian_student';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'relationship' => GuardianRelationship::class,
            'is_primary' => 'boolean',
        ];
    }
}
