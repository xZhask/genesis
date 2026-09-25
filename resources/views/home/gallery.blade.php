<section id="galeria" aria-labelledby="galeria-title">
    <div class="wrap">
        <x-section-head id="galeria-title" title="Momentos Génesis" lead="Toca una foto para verla en grande."
            :link="route('gallery')" link-text="Ver galería completa" />

        <div class="gallery" data-gallery>
            @foreach ($photos->take(8) as $photo)
                <button type="button" data-src="{{ $photo->image_url ? asset($photo->image_url) : '' }}" data-tone="{{ $photo->tone }}"
                    data-caption="{{ $photo->caption }}" aria-label="Ver foto: {{ $photo->caption }}">
                    <x-photo :src="$photo->image_url" :tone="$photo->tone" :label="$photo->image_url ? null : $photo->caption" />
                </button>
            @endforeach
        </div>
    </div>

    <dialog class="lightbox" data-lightbox aria-label="Foto ampliada">
        <button type="button" class="lb-btn close" data-lightbox-close aria-label="Cerrar"><x-icon name="close" /></button>
        <button type="button" class="lb-btn prev" data-lightbox-prev aria-label="Foto anterior"><x-icon name="chevron-left" /></button>
        <figure>
            <div class="frame" data-lightbox-frame></div>
            <figcaption class="cap" data-lightbox-caption aria-live="polite"></figcaption>
        </figure>
        <button type="button" class="lb-btn next" data-lightbox-next aria-label="Foto siguiente"><x-icon name="chevron-right" /></button>
    </dialog>
</section>
