{{-- Miniatura que abre el visor. $photo: GalleryPhoto::forLightbox() o DemoContent::photos() --}}
@props(['photo'])

<button type="button" data-src="{{ $photo['full'] }}" data-alt="{{ $photo['alt'] }}" data-caption="{{ $photo['caption'] }}"
    data-tone="{{ $photo['tone'] }}" aria-label="Ver foto: {{ $photo['alt'] }}">
    @if ($photo['thumb'])
        <img class="media" src="{{ $photo['thumb'] }}" alt="" width="{{ $photo['width'] }}" height="{{ $photo['height'] }}" loading="lazy" decoding="async">
    @else
        <x-photo :tone="$photo['tone']" :label="$photo['caption']" />
    @endif
</button>
