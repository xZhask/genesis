<?php

namespace App\Enums;

enum FeedbackType: string
{
    case Suggestion = 'suggestion';
    case Complaint = 'complaint';
    case Observation = 'observation';
    case Recognition = 'recognition';

    public function label(): string
    {
        return match ($this) {
            self::Suggestion => 'Sugerencia',
            self::Complaint => 'Queja',
            self::Observation => 'Observación',
            self::Recognition => 'Reconocimiento',
        };
    }

    /** Ayuda corta junto a cada opción del formulario. */
    public function hint(): string
    {
        return match ($this) {
            self::Suggestion => 'una idea para mejorar',
            self::Complaint => 'algo que no está bien',
            self::Observation => 'algo que el colegio debería saber',
            self::Recognition => 'algo que se está haciendo bien',
        };
    }
}
