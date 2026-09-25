@props(['event'])

@php
    $start = $event->starts_at;
    $end = $event->ends_at;

    if ($end && ! $end->isSameDay($start)) {
        $when = $start->isSameMonth($end)
            ? 'Del '.$start->day.' al '.$end->dayMonth()
            : 'Del '.$start->dayMonth().' al '.$end->dayMonth();
    } else {
        $when = $event->all_day ? 'Todo el día' : $start->shortTime();
    }

    $meta = collect([$when, $event->location])->filter()->join(' · ');
@endphp

<li class="ev">
    <time class="date" datetime="{{ $start->toDateString() }}">
        <b>{{ $start->format('d') }}</b>
        <small>{{ mb_strtoupper(rtrim($start->translatedFormat('M'), '.')) }}</small>
    </time>
    <div>
        <strong>{{ $event->title }}</strong>
        <div class="meta">{{ $meta }}</div>
        @if ($event->calendar_url)
            <a class="add" href="{{ $event->calendar_url }}">Agregar a mi calendario<span class="sr-only">: {{ $event->title }}</span></a>
        @endif
    </div>
</li>
