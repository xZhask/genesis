<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Matrícula de un estudiante en una sección durante un año lectivo. */
class Enrollment extends Model
{
    protected $fillable = ['student_id', 'section_id', 'school_year_id', 'status', 'withdrawn_on'];

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'withdrawn_on' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', EnrollmentStatus::Active);
    }

    /** Matricula o cambia de sección (una matrícula por año). */
    public static function place(Student $student, Section $section): self
    {
        $enrollment = static::firstOrNew(['student_id' => $student->id, 'school_year_id' => $section->school_year_id]);
        $enrollment->fill(['section_id' => $section->id]);
        if (! $enrollment->exists) {
            $enrollment->status = EnrollmentStatus::Active;
        }
        $enrollment->save();

        return $enrollment;
    }
}
