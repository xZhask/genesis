<?php

namespace App\Enums;

/** Quién ve una circular: todo el mundo en la web o solo las familias en el portal. */
enum ResourceVisibility: string
{
    case Families = 'families';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Families => 'Solo familias del portal',
            self::Public => 'Pública en la web',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Families => 'Salidas, cobros, reuniones o asuntos de un grupo. Se ve al ingresar al portal.',
            self::Public => 'Información general: calendario, matrículas, vacaciones. La ve cualquier persona.',
        };
    }
}
