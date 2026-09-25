<?php

namespace App\Exceptions;

use App\Models\Period;
use RuntimeException;

/** Escritura en un periodo cerrado (regla de seguridad 3). */
class PeriodClosedException extends RuntimeException
{
    public function __construct(public readonly Period $period)
    {
        parent::__construct("El {$period->name()} está cerrado: su asistencia y sus notas son de solo lectura. Si hay que corregir algo, pide a la administración que lo reabra.");
    }
}
