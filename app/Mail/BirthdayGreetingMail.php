<?php

namespace App\Mail;

use App\Models\BirthdayGreeting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Support\Facades\URL;

/** Tarjeta de cumpleaños, a la persona o a su acudiente. No lleva la edad. */
class BirthdayGreetingMail extends Mailable
{
    public function __construct(public BirthdayGreeting $greeting) {}

    private function firstName(): string
    {
        $person = $this->greeting->celebrant;

        return strtok($person instanceof Student ? $person->first_names : (string) $person->name, ' ');
    }

    /** Baja: el acudiente tiene un interruptor para todos sus avisos; los demás, uno propio. */
    private function unsubscribeUrl(): string
    {
        if ($this->greeting->guardian) {
            return URL::signedRoute('family-mail.unsubscribe', $this->greeting->guardian);
        }

        $person = $this->greeting->celebrant;
        $user = $person instanceof User ? $person : $person->user;

        return URL::signedRoute('birthday-mail.unsubscribe', $user);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "¡Feliz cumpleaños, {$this->firstName()}!");
    }

    public function headers(): Headers
    {
        return new Headers(text: ['List-Unsubscribe' => '<'.$this->unsubscribeUrl().'>']);
    }

    public function content(): Content
    {
        $person = $this->greeting->celebrant;

        return new Content(view: 'mail.birthday-card', with: [
            'firstName' => $this->firstName(),
            'fullName' => $person instanceof Student ? $person->fullName() : $person->name,
            'role' => $person instanceof User ? $person->role->label() : null,
            'guardian' => $this->greeting->guardian,
            'date' => $this->greeting->celebrated_on,
            'cardMessage' => config('school.birthdays.message'),
            'unsubscribeUrl' => $this->unsubscribeUrl(),
        ]);
    }
}
