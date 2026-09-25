<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Dirección legible y única a partir del título, fijada al crear: si luego se
 * corrige el título, los enlaces ya compartidos siguen funcionando.
 */
trait HasUniqueSlug
{
    protected static function bootHasUniqueSlug(): void
    {
        static::creating(function (self $model) {
            $base = Str::slug($model->title) ?: static::slugFallback();
            $slug = $base;
            $i = 2;
            while (static::where('slug', $slug)->exists()) {
                $slug = "{$base}-{$i}";
                $i++;
            }
            $model->slug = $slug;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function slugFallback(): string
    {
        return 'pagina';
    }
}
