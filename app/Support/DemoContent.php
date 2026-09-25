<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;

/**
 * Contenido de ejemplo para revisar el diseño en local mientras no existen
 * los módulos de noticias, eventos y galería. Nunca se activa en producción.
 *
 * Los objetos usan los mismos nombres de atributos que tendrán los modelos,
 * para que las vistas no cambien cuando se reemplace por datos reales.
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

    public static function posts(): Collection
    {
        return collect([
            ['Nuestra feria de ciencias llenó el colegio de ideas', 'Estudiantes de primaria y secundaria presentaron sus proyectos ante las familias y jurados invitados.', '2026-09-18', 't-azul'],
            ['Transición celebró el Día del Amor y la Amistad', 'Una mañana de juegos, cartas y compartir en familia.', '2026-09-12', 't-verde'],
            ['Así vivimos la semana del idioma', 'Lecturas en voz alta, cuentos ilustrados y una feria del libro hecha por los niños.', '2026-09-05', 't-sol'],
        ])->map(fn ($p) => new Fluent([
            'title' => $p[0],
            'excerpt' => $p[1],
            'published_at' => Carbon::parse($p[2]),
            'image_url' => null,
            'tone' => $p[3],
            'url' => route('news'),
        ]));
    }

    public static function events(): Collection
    {
        return collect([
            ['Entrega de boletines del tercer periodo', '2026-10-02 07:00', null, false, 'Sede principal'],
            ['Semana de receso escolar', '2026-10-05', '2026-10-09', true, null],
            ['Jornada de oración en familia', '2026-10-24 08:00', null, false, 'Patio central'],
        ])->map(fn ($e) => new Fluent([
            'title' => $e[0],
            'starts_at' => Carbon::parse($e[1]),
            'ends_at' => $e[2] ? Carbon::parse($e[2]) : null,
            'all_day' => $e[3],
            'location' => $e[4],
            'calendar_url' => route('calendar'),
        ]));
    }

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
        ])->map(fn ($p) => new Fluent([
            'caption' => $p[0],
            'image_url' => null,
            'tone' => $p[1],
        ]));
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
