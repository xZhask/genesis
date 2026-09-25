<?php

namespace App\Enums;

/** Datos de contacto que el acudiente puede pedir cambiar. */
enum ContactField: string
{
    case Phone = 'phone';
    case Email = 'email';

    public function label(): string
    {
        return match ($this) {
            self::Phone => 'Teléfono',
            self::Email => 'Correo',
        };
    }
}
