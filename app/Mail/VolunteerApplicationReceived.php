<?php

namespace App\Mail;

use App\Models\VolunteerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso al colegio de una nueva persona que quiere ser voluntaria. */
class VolunteerApplicationReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public VolunteerApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nuevo voluntario: {$this->application->name}",
            replyTo: $this->application->email
                ? [new Address($this->application->email, $this->application->name)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.volunteer-received');
    }
}
