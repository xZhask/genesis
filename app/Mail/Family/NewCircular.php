<?php

namespace App\Mail\Family;

use App\Models\Guardian;
use App\Models\Resource;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Aviso de una circular nueva (se lee completa en el portal). */
class NewCircular extends FamilyMailable
{
    public function __construct(Guardian $guardian, public Resource $circular)
    {
        parent::__construct($guardian);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Circular: {$this->circular->title}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.family.new-circular', with: $this->common() + [
            'circular' => $this->circular,
            'url' => $this->circular->isForFamiliesOnly()
                ? route('portal.guardian.circulars')
                : route('resources').'#'.$this->circular->anchor(),
        ]);
    }
}
