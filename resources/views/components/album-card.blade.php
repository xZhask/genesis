@props(['album'])

@php
    $cover = $album->cover();
@endphp

<article class="album-card">
    @if ($cover)
        <img class="media" src="{{ $cover->url('sm') }}" alt="" width="600" height="400" loading="lazy" decoding="async">
    @else
        <x-photo tone="t-azul" icon="image" />
    @endif
    <div class="body">
        <h3><a href="{{ $album->url() }}">{{ $album->title }}</a></h3>
        <p>
            <time datetime="{{ $album->taken_on->toDateString() }}">{{ $album->taken_on->longDate() }}</time>
            · {{ trans_choice(':count foto|:count fotos', $album->photos_count) }}
        </p>
    </div>
</article>
