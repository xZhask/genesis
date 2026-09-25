<?php

namespace App\Enums;

enum VolunteerStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nueva',
            self::Contacted => 'Contactada',
            self::Archived => 'Archivada',
        };
    }

    /** Clase del distintivo en el admin (reutiliza los colores de admisiones). */
    public function badge(): string
    {
        return match ($this) {
            self::New => 'received',
            self::Contacted => 'accepted',
            self::Archived => 'withdrawn',
        };
    }
}
