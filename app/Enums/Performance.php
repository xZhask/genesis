<?php

namespace App\Enums;

/** Escala nacional de desempeños (Decreto 1290 de 2009). */
enum Performance: string
{
    case Low = 'low';
    case Basic = 'basic';
    case High = 'high';
    case Superior = 'superior';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Bajo',
            self::Basic => 'Básico',
            self::High => 'Alto',
            self::Superior => 'Superior',
        };
    }
}
