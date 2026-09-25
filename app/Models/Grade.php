<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Los 13 grados del colegio (fijos, creados en la migración). */
class Grade extends Model
{
    protected $fillable = ['level', 'name', 'position'];

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class)
            ->withPivot('weekly_hours')
            ->withTimestamps()
            ->orderBy('subjects.position')
            ->orderBy('subjects.name');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('name');
    }

    /** Datos del nivel en config/school.php (nombre, color…). */
    public function levelConfig(): array
    {
        return collect(config('school.levels'))->firstWhere('key', $this->level);
    }

    public function isPreschool(): bool
    {
        return $this->level === 'preschool';
    }

    public static function ordered()
    {
        return static::orderBy('position')->get();
    }
}
