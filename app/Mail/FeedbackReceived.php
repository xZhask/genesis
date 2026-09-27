<?php

namespace App\Mail;

use App\Enums\FeedbackType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso a quienes leen el buzón. Por privacidad no lleva el mensaje, ni
 * quién lo escribió, ni la clase: solo el tipo y el enlace al panel.
 */
class FeedbackReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public FeedbackType $type) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nuevo mensaje en el buzón de sugerencias');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.feedback-received');
    }
}
