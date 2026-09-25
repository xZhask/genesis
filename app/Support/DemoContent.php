<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Contenido de ejemplo para revisar el diseño en local cuando la base de
 * datos está vacía (el inicio lo usa solo para completar lo que falte:
 * fotos de la galería y el resumen de Apóyanos). Nunca se activa en
 * producción.
 *
 * Los datos usan el mismo formato que los reales, para que las vistas no
 * cambien cuando se reemplacen.
 */
class DemoContent
{
    public static function enabled(): bool
    {
        return (bool) config('school.demo_content') && ! app()->isProduction();
    }

    public static function heroPhotos(): array
    {
        return ['demo/aula.jpg', 'demo/patio.jpg', 'demo/feria.jpg', 'demo/devocional.jpg'];
    }

    /** Mismo formato que GalleryPhoto::forLightbox(), sin imagen. */
    public static function photos(): Collection
    {
        return collect([
            ['Izada de bandera', 't-azul'],
            ['Salida pedagógica de primaria', 't-verde'],
            ['Día del idioma', 't-sol'],
            ['Coro institucional', 't-navy'],
            ['Juegos intercursos', 't-cielo'],
            ['Huerta escolar', 't-verde'],
            ['Graduación de Transición', 't-sol'],
            ['Clase de inglés', 't-azul'],
        ])->map(fn ($p) => [
            'thumb' => null,
            'full' => null,
            'alt' => $p[0],
            'caption' => $p[0],
            'width' => null,
            'height' => null,
            'tone' => $p[1],
        ]);
    }

    public static function support(): array
    {
        return [
            'accounts' => [
                ['label' => 'Banco de ejemplo, cuenta de ahorros', 'value' => '000-000000-00'],
                ['label' => 'Nequi', 'value' => '300 000 0000'],
                ['label' => 'Titular · NIT', 'value' => 'C.E.C. Génesis · 900.000.000-0'],
            ],
            'testimonial' => [
                'quote' => 'Pintamos tres aulas en un fin de semana. Ver la cara de los niños el lunes valió cada brochazo.',
                'author' => 'Acudiente y voluntaria (testimonio de ejemplo)',
            ],
            'volunteer_photos' => ['demo/patio.jpg', 'demo/feria.jpg', 'demo/aula.jpg'],
            'donors' => ['Aliado de ejemplo', 'Iglesia aliada', 'Fundación amiga', 'Empresa local'],
        ];
    }
}
