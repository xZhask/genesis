<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Área del plan de estudios: su nota es el promedio de sus materias (boletín). */
class Area extends Model
{
    protected $fillable = ['name', 'position'];

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class)->orderBy('position')->orderBy('name');
    }
}
