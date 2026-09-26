<?php

namespace App\Mail\Family;

use App\Models\ContactUpdateRequest;
use App\Models\Guardian;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Str;

/**
 * Aviso de seguridad al correo ANTERIOR cuando se aprueba un cambio de
 * correo: si no lo pidió la persona, puede avisar al colegio. Se envía
 * aunque haya desactivado los correos.
 */
class EmailChangedNotice extends FamilyMailable
{
    public function __construct(Guardian $guardian, public ContactUpdateRequest $request)
    {
        parent::__construct($guardian);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'El correo de tu cuenta del portal cambió');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.family.email-changed', with: $this->common() + [
            'masked' => self::mask($this->request->new_value),
            'date' => $this->request->reviewed_at ?? now(),
        ]);
    }

    /** «m•••••@example.com»: se ve a qué correo cambió sin exponerlo completo. */
    public static function mask(string $email): string
    {
        [$user, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return Str::substr($user, 0, 1).str_repeat('•', max(3, Str::length($user) - 1)).'@'.$domain;
    }
}
