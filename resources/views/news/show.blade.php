@php
    $shareText = $post->title.' '.$post->url();
@endphp

<x-layouts.public :title="$post->title" :description="$post->excerpt">
    @if ($preview)
        <div class="demo-note">Vista previa: esta noticia todavía no está publicada y solo la ves tú.</div>
    @endif

    <article class="article">
        <div class="wrap narrow">
            <p class="back"><a href="{{ route('news') }}">← Noticias</a></p>
            <time datetime="{{ ($post->published_at ?? now())->toDateString() }}">{{ ($post->published_at ?? now())->longDate() }}</time>
            <h1>{{ $post->title }}</h1>
            <p class="lead">{{ $post->excerpt }}</p>
        </div>

        @if ($post->cover_path)
            <figure class="article-cover wrap">
                <img src="{{ $post->coverUrl('lg') }}" alt="{{ $post->cover_alt }}" width="1200" loading="eager">
            </figure>
        @endif

        <div class="wrap narrow">
            <div class="prose">
                {{ $post->bodyHtml() }}
            </div>

            <div class="share">
                <span>Comparte esta noticia</span>
                <a class="btn btn-sm share-wa" href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener">
                    <x-icon name="whatsapp" /> WhatsApp
                </a>
                <button type="button" class="btn btn-line btn-sm" data-copy="{{ $post->url() }}">Copiar enlace</button>
            </div>
        </div>
    </article>

    @if ($others->isNotEmpty())
        <section class="news-bg" aria-labelledby="otras-title">
            <div class="wrap">
                <x-section-head id="otras-title" title="Otras noticias" :link="route('news')" link-text="Ver todas" />
                <div class="news-row">
                    @foreach ($others as $other)
                        <x-post-card :post="$other" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
