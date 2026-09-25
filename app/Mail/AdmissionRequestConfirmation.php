<?php

namespace App\Mail;

use App\Models\AdmissionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Copia de la solicitud para el acudiente. */
class AdmissionRequestConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public AdmissionRequest $admission) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Recibimos tu solicitud de pre-inscripción ({$this->admission->code})",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.admission-confirmation', with: [
            'responseTime' => config('school.admissions.response_time'),
        ]);
    }
}
