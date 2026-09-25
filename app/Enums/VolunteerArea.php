<?php

namespace App\Enums;

/** Formas de ayudar que se ofrecen en el formulario (textos provisionales de UX). */
enum VolunteerArea: string
{
    case Spaces = 'spaces';
    case Events = 'events';
    case Skills = 'skills';
    case Reading = 'reading';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spaces => 'Mejorar espacios',
            self::Events => 'Apoyar eventos y salidas',
            self::Skills => 'Compartir mi oficio',
            self::Reading => 'Leer con los niños',
            self::Other => 'Otra forma',
        };
    }

    public function description(): ?string
    {
        return match ($this) {
            self::Spaces => 'Pintura, arreglos, huerta y zonas verdes.',
            self::Events => 'Acompañar actividades, celebraciones y salidas pedagógicas.',
            self::Skills => 'Una charla o un taller sobre lo que sabes hacer.',
            self::Reading => 'Lectura en voz alta y apoyo a la biblioteca.',
            self::Other => null,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Spaces => 'tree',
            self::Events => 'calendar',
            self::Skills => 'puzzle',
            self::Reading => 'book',
            self::Other => 'form',
        };
    }
}
