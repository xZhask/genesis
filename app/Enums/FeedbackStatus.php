<?php

namespace App\Enums;

enum FeedbackStatus: string
{
    case Received = 'received';
    case InReview = 'in_review';
    case Answered = 'answered';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Recibido',
            self::InReview => 'En revisión',
            self::Answered => 'Respondido',
        };
    }

    /** Clase del distintivo (reutiliza los colores de admisiones). */
    public function badge(): string
    {
        return match ($this) {
            self::Received => 'received',
            self::InReview => 'in_review',
            self::Answered => 'accepted',
        };
    }
}
