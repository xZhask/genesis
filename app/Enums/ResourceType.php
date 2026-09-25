<?php

namespace App\Enums;

/** Tipos de recurso para acudientes. El orden es el de la página pública. */
enum ResourceType: string
{
    case Circular = 'circular';
    case Supplies = 'supplies';
    case Uniform = 'uniform';
    case Schedule = 'schedule';

    public function label(): string
    {
        return match ($this) {
            self::Circular => 'Circular',
            self::Supplies => 'Lista de útiles',
            self::Uniform => 'Uniforme',
            self::Schedule => 'Horario',
        };
    }

    public function plural(): string
    {
        return match ($this) {
            self::Circular => 'Circulares',
            self::Supplies => 'Útiles',
            self::Uniform => 'Uniformes',
            self::Schedule => 'Horarios',
        };
    }

    /** Ancla de la sección en /recursos (los accesos rápidos del inicio la usan). */
    public function anchor(): string
    {
        return match ($this) {
            self::Circular => 'circulares',
            self::Supplies => 'utiles',
            self::Uniform => 'uniformes',
            self::Schedule => 'horarios',
        };
    }

    /** Valor en la URL del admin (?tipo=…). */
    public static function fromAnchor(?string $anchor): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->anchor() === $anchor) {
                return $case;
            }
        }

        return null;
    }
}
