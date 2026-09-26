<?php

namespace App\Mail\Family;

use App\Models\Guardian;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Support\Facades\URL;

/**
 * Base de los correos a las familias: saludo con el nombre del acudiente y
 * un enlace para dejar de recibirlos sin iniciar sesión (enlace firmado).
 * Nunca llevan notas ni datos de rendimiento: solo avisan y llevan al portal.
 */
abstract class FamilyMailable extends Mailable
{
    public function __construct(public Guardian $guardian) {}

    public function unsubscribeUrl(): string
    {
        return URL::signedRoute('family-mail.unsubscribe', $this->guardian);
    }

    public function headers(): Headers
    {
        return new Headers(text: ['List-Unsubscribe' => '<'.$this->unsubscribeUrl().'>']);
    }

    /** Datos comunes para las vistas. */
    protected function common(): array
    {
        return [
            'guardian' => $this->guardian,
            'unsubscribeUrl' => $this->unsubscribeUrl(),
        ];
    }
}
