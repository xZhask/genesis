<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';
    case Guardian = 'guardian';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Teacher => 'Docente',
            self::Student => 'Estudiante',
            self::Guardian => 'Acudiente',
        };
    }
}
