<?php

namespace App\Models\Concerns;

use App\Enums\DocumentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Estudiantes y acudientes: nombre, documento y cuenta del portal (opcional).
 * El nombre y el documento de la cuenta se mantienen iguales a los de la
 * persona, porque con ese documento se ingresa.
 */
trait IsPerson
{
    protected static function bootIsPerson(): void
    {
        static::saved(function (self $person) {
            if ($person->user && $person->wasChanged(['first_names', 'last_names', 'document_number'])) {
                $person->user->forceFill([
                    'name' => $person->fullName(),
                    'document_number' => $person->document_number,
                ])->save();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function documentNumber(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => DocumentType::normalize($value));
    }

    public function fullName(): string
    {
        return "{$this->first_names} {$this->last_names}";
    }

    /** Para listas ordenadas por apellido: "Pérez Díaz, Andrés". */
    public function sortName(): string
    {
        return "{$this->last_names}, {$this->first_names}";
    }

    /** "TI 1045678". */
    public function documentLabel(): string
    {
        return "{$this->document_type->value} {$this->document_number}";
    }

    public function scopeAlphabetical(Builder $query): void
    {
        $query->orderBy('last_names')->orderBy('first_names');
    }

    /** Busca por nombre, apellido o documento (con o sin puntos). */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);
        if ($term === '') {
            return;
        }

        // Cada palabra debe aparecer en los nombres o en los apellidos
        $query->where(fn (Builder $q) => $q
            ->where('document_number', DocumentType::normalize($term))
            ->orWhere(function (Builder $names) use ($term) {
                foreach (preg_split('/\s+/', $term) as $word) {
                    $names->where(fn (Builder $w) => $w
                        ->where('first_names', 'like', "%{$word}%")
                        ->orWhere('last_names', 'like', "%{$word}%"));
                }
            }));
    }
}
