<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\EnrollmentStatus;
use App\Models\Concerns\IsPerson;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory, IsPerson;

    protected $fillable = ['document_type', 'document_number', 'first_names', 'last_names', 'birth_date'];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'birth_date' => 'date',
        ];
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class)
            ->using(GuardianStudent::class)
            ->withPivot(['relationship', 'is_primary'])
            ->withTimestamps()
            ->orderByPivot('is_primary', 'desc')
            ->orderBy('last_names');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function enrollmentFor(?SchoolYear $year): ?Enrollment
    {
        if (! $year) {
            return null;
        }

        return $this->relationLoaded('enrollments')
            ? $this->enrollments->firstWhere('school_year_id', $year->id)
            : $this->enrollments()->where('school_year_id', $year->id)->first();
    }

    /**
     * Cuenta propia desde el grado configurado (6.°); antes entra solo el
     * acudiente. Se decide por la matrícula activa del año indicado.
     */
    public function canHaveAccount(?SchoolYear $year): bool
    {
        $enrollment = $this->enrollmentFor($year);

        return $enrollment?->status === EnrollmentStatus::Active
            && $enrollment->section->grade->position >= static::accountsFromPosition();
    }

    /** Sin cuenta y matriculados (activos) en el año, desde el grado configurado. */
    public function scopeAwaitingAccount(Builder $query, ?SchoolYear $year): void
    {
        $query->whereNull('user_id')->whereHas('enrollments', fn ($e) => $e
            ->where('school_year_id', $year?->id)
            ->where('status', EnrollmentStatus::Active)
            ->whereHas('section.grade', fn ($g) => $g->where('position', '>=', static::accountsFromPosition())));
    }

    public static function accountsFromPosition(): int
    {
        return (int) Grade::where('name', config('school.academic.student_accounts_from'))->value('position') ?: PHP_INT_MAX;
    }

    /** Relación del acudiente principal, si hay. */
    public function primaryGuardian(): ?Guardian
    {
        return $this->guardians->first(fn (Guardian $g) => $g->pivot->is_primary);
    }
}
