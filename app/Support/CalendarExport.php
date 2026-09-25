<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Carbon;

/**
 * "Agregar a mi calendario": archivo .ics (iPhone, Outlook y la mayoría de apps)
 * y enlace de Google Calendar (Android). Formato iCalendar, RFC 5545.
 */
class CalendarExport
{
    public static function ics(Event $event): string
    {
        [$start, $end] = self::range($event);
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'genesis';

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Centro Educativo Cristiano Genesis//Calendario escolar//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            "UID:event-{$event->id}@{$host}",
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            ...($event->all_day || $event->isMultiDay()
                ? ['DTSTART;VALUE=DATE:'.$start, 'DTEND;VALUE=DATE:'.$end]
                : ['DTSTART:'.$start, 'DTEND:'.$end]),
            'SUMMARY:'.self::escape($event->title),
        ];

        if ($event->location) {
            $lines[] = 'LOCATION:'.self::escape($event->location);
        }

        $lines[] = 'DESCRIPTION:'.self::escape(trim(($event->description ?? '')."\n\n".config('school.name')));
        $lines[] = 'URL:'.route('calendar');
        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map(self::fold(...), $lines))."\r\n";
    }

    public static function googleUrl(Event $event): string
    {
        [$start, $end] = self::range($event);

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $event->title,
            'dates' => "{$start}/{$end}",
            'details' => trim(($event->description ?? '')."\n\n".config('school.name')),
            'location' => $event->location ?? '',
            'ctz' => config('app.timezone'),
        ]);
    }

    /** Inicio y fin en formato iCalendar. Los eventos de día completo terminan el día siguiente (exclusivo). */
    private static function range(Event $event): array
    {
        if ($event->all_day || $event->isMultiDay()) {
            return [
                $event->starts_at->format('Ymd'),
                $event->lastDay()->addDay()->format('Ymd'),
            ];
        }

        $end = $event->ends_at ?? $event->starts_at->copy()->addHour();

        return [self::utc($event->starts_at), self::utc($end)];
    }

    private static function utc(Carbon $date): string
    {
        return $date->copy()->utc()->format('Ymd\THis\Z');
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $text);
    }

    /** Las líneas de más de 75 bytes se parten con CRLF + espacio, sin cortar caracteres UTF-8. */
    private static function fold(string $line): string
    {
        $out = '';
        $current = '';

        foreach (mb_str_split($line) as $char) {
            if (strlen($current.$char) > 75) {
                $out .= $current."\r\n ";
                $current = '';
            }
            $current .= $char;
        }

        return $out.$current;
    }
}
