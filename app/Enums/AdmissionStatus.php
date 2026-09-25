<?php

namespace App\Enums;

/** Estados de una solicitud de pre-inscripción (docs/alcance-y-modelo.md). */
enum AdmissionStatus: string
{
    case Received = 'received';
    case InReview = 'in_review';
    case InterviewScheduled = 'interview_scheduled';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Recibida',
            self::InReview => 'En revisión',
            self::InterviewScheduled => 'Entrevista agendada',
            self::Accepted => 'Aceptada',
            self::Rejected => 'No admitida',
            self::Withdrawn => 'Retirada',
        };
    }
}
