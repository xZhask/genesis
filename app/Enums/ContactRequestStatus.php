<?php

namespace App\Enums;

enum ContactRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En revisión',
            self::Approved => 'Aprobada',
            self::Rejected => 'No aprobada',
        };
    }

    /** Clase del distintivo (reutiliza los colores de admisiones). */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'received',
            self::Approved => 'accepted',
            self::Rejected => 'rejected',
        };
    }
}
