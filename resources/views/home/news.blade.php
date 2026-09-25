<section class="news-bg" id="noticias" aria-labelledby="noticias-title">
    <div class="wrap">
        <x-section-head id="noticias-title" title="Noticias y eventos" lead="Lo que está pasando en Génesis."
            :link="route('news')" link-text="Ver todas las noticias" />

        <div class="news-layout">
            @if ($posts->isNotEmpty())
                <div class="news">
                    @foreach ($posts->take(3) as $post)
                        <article @class(['post', 'feature' => $loop->first])>
                            <x-photo :src="$post->image_url" :tone="$post->tone" icon="image" />
                            <div class="body">
                                <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->longDate() }}</time>
                                <h3><a href="{{ $post->url }}">{{ $post->title }}</a></h3>
                                <p>{{ $post->excerpt }}</p>
                                <span class="more" aria-hidden="true">Ver noticia</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            @if ($events->isNotEmpty())
                <aside class="events" aria-labelledby="eventos-title">
                    <h3 id="eventos-title">Próximos eventos</h3>
                    <ul>
                        @foreach ($events->take(3) as $event)
                            <x-event-item :event="$event" />
                        @endforeach
                    </ul>
                    <a class="all" href="{{ route('calendar') }}">Ver calendario completo</a>
                </aside>
            @endif
        </div>
    </div>
</section>
