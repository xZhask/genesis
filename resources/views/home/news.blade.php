<section class="news-bg" id="noticias" aria-labelledby="noticias-title">
    <div class="wrap">
        <x-section-head id="noticias-title" title="Calendario y noticias" lead="Lo que viene y lo que está pasando en Génesis."
            :link="route('calendar')" link-text="Ver calendario completo" />

        <div class="news-layout">
            @if ($posts->isNotEmpty())
                <div class="news">
                    @foreach ($posts as $post)
                        <x-post-card :post="$post" :feature="$loop->first" />
                    @endforeach
                </div>
            @endif

            @if ($events->isNotEmpty())
                <aside class="events" aria-labelledby="eventos-title">
                    <h3 id="eventos-title">Próximos eventos</h3>
                    <ul>
                        @foreach ($events as $event)
                            <x-event-item :event="$event" />
                        @endforeach
                    </ul>
                    <a class="all" href="{{ route('calendar') }}">Ver calendario completo</a>
                </aside>
            @endif
        </div>

        @if ($posts->isNotEmpty())
            <p class="more-news"><a href="{{ route('news') }}">Ver todas las noticias</a></p>
        @endif
    </div>
</section>
