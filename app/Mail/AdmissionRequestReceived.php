<?php

namespace App\Mail;

use App\Models\AdmissionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso al colegio de una nueva pre-inscripción. */
class AdmissionRequestReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public AdmissionRequest $admission) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nueva pre-inscripción {$this->admission->code}: {$this->admission->studentFullName()} ({$this->admission->grade})",
            replyTo: $this->admission->guardian_email
                ? [new Address($this->admission->guardian_email, $this->admission->guardian_name)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.admission-received');
    }
}
