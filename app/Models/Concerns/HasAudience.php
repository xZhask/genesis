<?php

namespace App\Models\Concerns;

use App\Enums\ResourceVisibility;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Builder;

/**
 * Contenido que puede ser público o solo para las familias del portal, y en
 * ese caso ir dirigido a ciertos grados (circulares y eventos).
 * Requiere las columnas visibility (ResourceVisibility) y audience (array).
 */
trait HasAudience
{
    /** Lo que se ve en la web pública. */
    public function scopePublicWeb(Builder $query): void
    {
        $query->where('visibility', ResourceVisibility::Public);
    }

    public function isForFamiliesOnly(): bool
    {
        return $this->visibility === ResourceVisibility::Families;
    }

    /** ¿Va dirigido a alguno de estos grados? Sin grados elegidos, es para todas las familias. */
    public function isForGrades(array $grades): bool
    {
        return empty($this->audience) || array_intersect($this->audience, $grades) !== [];
    }

    /** «Todas las familias» o «Familias de 5.° y 6.°». */
    public function audienceLabel(): string
    {
        if (empty($this->audience)) {
            return 'Todas las familias';
        }
        // En el orden del colegio, no en el que se marcaron
        $grades = array_values(array_intersect(Resource::grades(), $this->audience));

        return 'Familias de '.collect($grades)->join(', ', ' y ');
    }
}
