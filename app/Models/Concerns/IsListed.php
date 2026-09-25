<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/** Elementos que el admin muestra u oculta y ordena (columnas is_visible y position). */
trait IsListed
{
    public function initializeIsListed(): void
    {
        $this->mergeCasts(['is_visible' => 'boolean']);
    }

    public function scopeVisible(Builder $query): void
    {
        $query->where('is_visible', true)->ordered();
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }
}
