{{-- Una persona en la lista de cumpleaños. $card: mostrar cuándo es y a dónde va la tarjeta. --}}
@php
    $recipient = $card ? App\Support\Birthdays::recipient($row['person']) : null;
    $wasSent = isset($sent[$row['person']->getMorphClass().'|'.$row['person']->getKey()]);
    $when = match (true) {
        ! $card => $row['day'].' de '.Illuminate\Support\Carbon::create(2000, $row['month'], 1)->translatedFormat('F'),
        $row['days'] === 0 => 'Hoy',
        $row['days'] === 1 => 'Mañana',
        default => 'En '.$row['days'].' días',
    };
@endphp
<a class="list-row" href="{{ $row['url'] }}">
    <span>
        <strong>{{ $row['name'] }}</strong>
        <small>
            {{ $row['detail'] }} · {{ $when }}
            @if ($card)
                @if ($wasSent)
                    · Tarjeta enviada
                @elseif (! config('school.birthdays.enabled'))
                    · Tarjetas desactivadas
                @elseif (! $recipient)
                    · No recibe tarjeta: revisa su correo o la cuenta de su acudiente
                @elseif ($recipient['guardian'])
                    · La tarjeta le llega a {{ $recipient['guardian']->fullName() }}
                @else
                    · La tarjeta le llega a su correo
                @endif
            @endif
        </small>
    </span>
    <span aria-hidden="true">→</span>
</a>
