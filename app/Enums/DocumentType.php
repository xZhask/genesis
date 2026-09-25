<?php

namespace App\Enums;

/** Tipos de documento de identidad en Colombia. */
enum DocumentType: string
{
    case CivilRegistry = 'RC';
    case IdentityCard = 'TI';
    case Citizenship = 'CC';
    case Foreigner = 'CE';
    case TemporaryProtection = 'PPT';
    case Passport = 'PA';

    public function label(): string
    {
        return match ($this) {
            self::CivilRegistry => 'Registro civil',
            self::IdentityCard => 'Tarjeta de identidad',
            self::Citizenship => 'Cédula de ciudadanía',
            self::Foreigner => 'Cédula de extranjería',
            self::TemporaryProtection => 'Permiso por protección temporal',
            self::Passport => 'Pasaporte',
        };
    }

    /** Número sin puntos, espacios ni guiones, en mayúsculas: "1.045.678" → "1045678". */
    public static function normalize(?string $number): string
    {
        return strtoupper(preg_replace('/[\s.\-]/u', '', (string) $number));
    }
}
