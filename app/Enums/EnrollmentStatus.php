<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Matriculado',
            self::Withdrawn => 'Retirado',
        };
    }
}
