@use('App\Support\CalendarExport')

<x-layouts.portal title="Calendario">
    <div class="admin-head">
        <h1>Calendario</h1>
        <p class="lead-sm">Los próximos eventos del colegio, incluidos los que son solo para tu familia.
            El calendario general también está en <a href="{{ route('calendar') }}">la página del colegio</a>.</p>
    </div>

    @if ($groups->isEmpty())
        <p class="empty panel">No hay eventos próximos. Cuando el colegio agregue uno, aparecerá aquí.</p>
    @else
        @foreach ($groups as $month => $events)
            <section class="portal-month" aria-labelledby="mes-{{ $loop->index }}">
                <h2 id="mes-{{ $loop->index }}" class="section-title">{{ $month }}</h2>
                <ul class="portal-circulars">
                    @foreach ($events as $event)
                        <li class="panel portal-event">
                            <p class="circular-meta">
                                <time datetime="{{ $event->starts_at->toDateString() }}">
                                    {{ $event->isMultiDay() ? $event->whenLabel() : ucfirst($event->starts_at->translatedFormat('l j \d\e F')) }}
                                </time>
                                @if ($event->isForFamiliesOnly())
                                    <span class="badge badge-in_review">{{ $event->audienceLabel() }}</span>
                                @endif
                                @if ($event->levelName())
                                    <span class="badge badge-received">{{ $event->levelName() }}</span>
                                @endif
                            </p>
                            <h3>{{ $event->title }}</h3>
                            @if (! $event->isMultiDay() || $event->location)
                                <p class="event-meta">{{ collect([$event->isMultiDay() ? null : $event->whenLabel(), $event->location])->filter()->join(' · ') }}</p>
                            @endif
                            @if ($event->description)
                                <p>{{ $event->description }}</p>
                            @endif
                            <p class="event-add">
                                Agregar a mi calendario:
                                <a href="{{ CalendarExport::googleUrl($event) }}" target="_blank" rel="noopener">Google</a> ·
                                <a href="{{ route('calendar.ics', $event) }}">Otro (.ics)</a>
                            </p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    @endif
</x-layouts.portal>
