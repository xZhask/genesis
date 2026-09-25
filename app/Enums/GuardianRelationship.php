<?php

namespace App\Enums;

enum GuardianRelationship: string
{
    case Mother = 'mother';
    case Father = 'father';
    case Grandparent = 'grandparent';
    case UncleAunt = 'uncle_aunt';
    case Sibling = 'sibling';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Mother => 'Madre',
            self::Father => 'Padre',
            self::Grandparent => 'Abuela o abuelo',
            self::UncleAunt => 'Tía o tío',
            self::Sibling => 'Hermana o hermano',
            self::Other => 'Otro familiar o tutor',
        };
    }
}
