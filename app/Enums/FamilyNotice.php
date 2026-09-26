<?php

namespace App\Enums;

/** Correos que el colegio envía a las familias (fase 3). */
enum FamilyNotice: string
{
    case EventReminder = 'event_reminder';
    case ReportCards = 'report_cards';
    case ContactReviewed = 'contact_reviewed';
    case EmailChanged = 'email_changed';
    case NewCircular = 'new_circular';

    /**
     * Se envían aunque la persona haya desactivado los avisos: el de
     * seguridad (cambio de correo) y la respuesta a una solicitud suya.
     */
    public function ignoresOptOut(): bool
    {
        return in_array($this, [self::EmailChanged, self::ContactReviewed], true);
    }
}
