@php
    // Conserva los filtros al cambiar de vista, nivel o mes
    $url = fn (array $changes = []) => route('calendar', array_filter([
        'vista' => $view === 'month' ? 'mes' : null,
        'nivel' => $levelSlug,
        'mes' => $view === 'month' ? $month->format('Y-m') : null,
        ...$changes,
    ]));
    $weekdays = ['lun', 'mar', 'mié', 'jue', 'vie', 'sáb', 'dom'];
@endphp

<x-layouts.public title="Calendario escolar" description="Calendario escolar del Centro Educativo Cristiano Génesis: entregas de boletines, recesos, celebraciones y reuniones de padres.">
    <x-page-head eyebrow="Calendario y noticias" title="Calendario escolar"
        lead="Entregas de boletines, recesos, celebraciones y reuniones. Agrega cualquier fecha a tu calendario con un toque." />

    <section class="cal" aria-label="Eventos">
        <div class="wrap">
            <div class="cal-toolbar">
                <nav class="segmented" aria-label="Tipo de vista">
                    <a href="{{ $url(['vista' => null, 'mes' => null]) }}" @if ($view === 'list') aria-current="page" @endif>Lista</a>
                    <a href="{{ $url(['vista' => 'mes', 'mes' => ($month ?? now())->format('Y-m')]) }}" @if ($view === 'month') aria-current="page" @endif>Mes</a>
                </nav>

                <nav class="chips" aria-label="Filtrar por nivel">
                    <a href="{{ $url(['nivel' => null]) }}" @if (! $levelSlug) aria-current="page" @endif>Todo el colegio</a>
                    @foreach ($levels as $level)
                        <a href="{{ $url(['nivel' => $level['slug']]) }}" class="chip-{{ $level['key'] }}"
                            @if ($levelSlug === $level['slug']) aria-current="page" @endif>{{ $level['short'] }}</a>
                    @endforeach
                </nav>
            </div>

            @if ($view === 'list')
                @forelse ($groups as $monthName => $events)
                    <div class="cal-month">
                        <h2>{{ $monthName }}</h2>
                        <ul class="cal-list">
                            @foreach ($events as $event)
                                <x-event-item :event="$event" show-level />
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="cal-empty">No hay eventos próximos{{ $levelSlug ? ' para este nivel' : '' }}. Vuelve pronto: el colegio publica las fechas a medida que se confirman.</p>
                @endforelse
            @else
                <div class="month-head">
                    <a class="icon-btn" href="{{ $url(['mes' => $month->copy()->subMonth()->format('Y-m')]) }}" aria-label="Mes anterior"><x-icon name="chevron-left" /></a>
                    <h2>{{ \Illuminate\Support\Str::ucfirst($month->translatedFormat('F \d\e Y')) }}</h2>
                    <a class="icon-btn" href="{{ $url(['mes' => $month->copy()->addMonth()->format('Y-m')]) }}" aria-label="Mes siguiente"><x-icon name="chevron-right" /></a>
                </div>

                {{-- Cuadrícula visual; la lista de abajo tiene la misma información para lectores de pantalla --}}
                <div class="month-grid" aria-hidden="true">
                    @foreach ($weekdays as $weekday)
                        <div class="wd">{{ $weekday }}</div>
                    @endforeach
                    @foreach ($weeks as $week)
                        @foreach ($week as $day)
                            <div @class(['day', 'out' => ! $day['inMonth'], 'today' => $day['date']->isToday(), 'has' => $day['events']->isNotEmpty()])>
                                <span class="n">{{ $day['date']->day }}</span>
                                @foreach ($day['events']->take(2) as $event)
                                    <span class="pill lvl-{{ $event->level ?? 'all' }}">{{ $event->title }}</span>
                                @endforeach
                                @if ($day['events']->count() > 2)
                                    <span class="pill more">+{{ $day['events']->count() - 2 }}</span>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </div>

                <div class="cal-month">
                    <h2 class="sr-only">Eventos del mes</h2>
                    @if ($monthEvents->isEmpty())
                        <p class="cal-empty">No hay eventos en este mes{{ $levelSlug ? ' para este nivel' : '' }}.</p>
                    @else
                        <ul class="cal-list">
                            @foreach ($monthEvents as $event)
                                <x-event-item :event="$event" show-level />
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        </div>
    </section>

    @if ($posts->isNotEmpty())
        <section class="news-bg" aria-labelledby="ultimas-title">
            <div class="wrap">
                <x-section-head id="ultimas-title" title="Últimas noticias" :link="route('news')" link-text="Ver todas las noticias" />
                <div class="news-row">
                    @foreach ($posts as $post)
                        <x-post-card :post="$post" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
