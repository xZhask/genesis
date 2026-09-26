<?php

namespace App\Mail\Family;

use App\Enums\ContactRequestStatus;
use App\Models\ContactUpdateRequest;
use App\Models\Guardian;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Resultado de la solicitud de cambio de teléfono o correo. */
class ContactRequestReviewed extends FamilyMailable
{
    public function __construct(Guardian $guardian, public ContactUpdateRequest $request)
    {
        parent::__construct($guardian);
    }

    public function envelope(): Envelope
    {
        $field = mb_strtolower($this->request->field->label());

        return new Envelope(subject: $this->request->status === ContactRequestStatus::Approved
            ? "Actualizamos tu {$field} en el portal"
            : "Tu solicitud de cambio de {$field} no se aprobó");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.family.contact-reviewed', with: $this->common() + [
            'request' => $this->request,
            'approved' => $this->request->status === ContactRequestStatus::Approved,
        ]);
    }
}
