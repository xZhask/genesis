<x-layouts.public title="Noticias" description="Noticias del Centro Educativo Cristiano Génesis: actividades, celebraciones y logros de nuestros estudiantes.">
    <x-page-head eyebrow="Calendario y noticias" title="Noticias" lead="Actividades, celebraciones y logros de nuestra comunidad educativa." />

    <section class="news-page">
        <div class="wrap news-layout">
            <div>
                @if ($posts->isEmpty())
                    <p class="cal-empty">Pronto publicaremos las primeras noticias del colegio.</p>
                @else
                    <div class="news">
                        @foreach ($posts as $post)
                            <x-post-card :post="$post" :feature="$loop->first && $posts->onFirstPage()" />
                        @endforeach
                    </div>
                    {{ $posts->links('partials.pagination') }}
                @endif
            </div>

            <aside class="events" aria-labelledby="eventos-title">
                <h2 id="eventos-title" class="h3">Próximos eventos</h2>
                @if ($events->isEmpty())
                    <p class="cal-empty">No hay eventos próximos.</p>
                @else
                    <ul>
                        @foreach ($events as $event)
                            <x-event-item :event="$event" />
                        @endforeach
                    </ul>
                @endif
                <a class="all" href="{{ route('calendar') }}">Ver calendario completo</a>
            </aside>
        </div>
    </section>
</x-layouts.public>
