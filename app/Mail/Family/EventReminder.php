<?php

namespace App\Mail\Family;

use App\Models\Event;
use App\Models\Guardian;
use App\Support\CalendarExport;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Recordatorio de un evento del calendario escolar. */
class EventReminder extends FamilyMailable
{
    public function __construct(Guardian $guardian, public Event $event)
    {
        parent::__construct($guardian);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Recordatorio: {$this->event->title}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.family.event-reminder', with: $this->common() + [
            'event' => $this->event,
            'googleUrl' => CalendarExport::googleUrl($this->event),
            'icsUrl' => route('calendar.ics', $this->event),
        ]);
    }
}
