<?php

namespace App\Enums;

/** Componentes de la nota del periodo, cada uno con su logro. */
enum EvaluationComponent: string
{
    case Knowing = 'knowing';
    case Doing = 'doing';
    case Being = 'being';

    public function label(): string
    {
        return match ($this) {
            self::Knowing => 'Saber',
            self::Doing => 'Hacer',
            self::Being => 'Ser',
        };
    }

    /** Qué se evalúa, como ayuda para el docente. */
    public function hint(): string
    {
        return match ($this) {
            self::Knowing => 'Conocimientos: lo que el estudiante comprende',
            self::Doing => 'Procedimientos: lo que el estudiante sabe hacer',
            self::Being => 'Actitudes: cómo participa y convive',
        };
    }
}
