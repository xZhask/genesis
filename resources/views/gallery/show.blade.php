@php
    $count = $album->photos->count();
    $description = $album->description ?: "Fotos de «{$album->title}» en el Centro Educativo Cristiano Génesis.";
@endphp

<x-layouts.public :title="$album->title" :description="$description">
    @if ($preview)
        <div class="demo-note">Vista previa: este álbum todavía no está publicado{{ $count ? '' : ' ni tiene fotos' }} y solo lo ves tú.</div>
    @endif

    <section class="album-page">
        <div class="wrap">
            <p class="back"><a href="{{ route('gallery') }}">← Galería</a></p>
            <h1>{{ $album->title }}</h1>
            <p class="album-meta">
                <time datetime="{{ $album->taken_on->toDateString() }}">{{ $album->taken_on->longDate() }}</time>
                · {{ trans_choice(':count foto|:count fotos', $count) }}
            </p>
            @if ($album->description)
                <p class="lead">{{ $album->description }}</p>
            @endif

            @if ($count)
                <p class="album-hint">Toca una foto para verla en grande.</p>
                <div class="photo-grid" data-gallery>
                    @foreach ($album->photos as $photo)
                        <x-gallery-photo :photo="$photo->forLightbox()" />
                    @endforeach
                </div>
            @endif

            <div class="share">
                <span>Comparte este álbum</span>
                <a class="btn btn-sm share-wa" href="https://wa.me/?text={{ rawurlencode($album->title.' '.$album->url()) }}" target="_blank" rel="noopener">
                    <x-icon name="whatsapp" /> WhatsApp
                </a>
                <button type="button" class="btn btn-line btn-sm" data-copy="{{ $album->url() }}">Copiar enlace</button>
            </div>
        </div>

        @include('partials.lightbox')
    </section>

    @if ($others->isNotEmpty())
        <section class="news-bg" aria-labelledby="otros-title">
            <div class="wrap">
                <x-section-head id="otros-title" title="Otros álbumes" :link="route('gallery')" link-text="Ver todos" />
                <div class="albums">
                    @foreach ($others as $other)
                        <x-album-card :album="$other" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
