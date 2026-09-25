<?php

namespace App\Enums;

enum VolunteerAvailability: string
{
    case WeekdayMorning = 'weekday_morning';
    case WeekdayAfternoon = 'weekday_afternoon';
    case Weekends = 'weekends';
    case Occasional = 'occasional';

    public function label(): string
    {
        return match ($this) {
            self::WeekdayMorning => 'Entre semana, en la mañana',
            self::WeekdayAfternoon => 'Entre semana, en la tarde',
            self::Weekends => 'Fines de semana',
            self::Occasional => 'Solo en actividades puntuales',
        };
    }
}
