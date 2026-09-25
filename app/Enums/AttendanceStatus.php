<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case Excused = 'excused';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Presente',
            self::Absent => 'Ausente',
            self::Late => 'Tarde',
            self::Excused => 'Excusa',
        };
    }

    /** Inicial para la planilla del docente. */
    public function short(): string
    {
        return match ($this) {
            self::Present => 'P',
            self::Absent => 'A',
            self::Late => 'T',
            self::Excused => 'E',
        };
    }
}
