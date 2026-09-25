<section id="galeria" aria-labelledby="galeria-title">
    <div class="wrap">
        <x-section-head id="galeria-title" title="Momentos Génesis" lead="Toca una foto para verla en grande."
            :link="route('gallery')" link-text="Ver galería completa" />

        <div class="gallery" data-gallery>
            @foreach ($photos->take(8) as $photo)
                <x-gallery-photo :photo="$photo" />
            @endforeach
        </div>
    </div>

    @include('partials.lightbox')
</section>
