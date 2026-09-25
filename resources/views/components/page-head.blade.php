{{-- Encabezado de las páginas internas --}}
@props(['title', 'lead' => null, 'eyebrow' => null])

<section class="page-head" aria-labelledby="page-title">
    <div class="wrap">
        @if ($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 id="page-title">{{ $title }}</h1>
        @if ($lead)
            <p class="lead">{{ $lead }}</p>
        @endif
        @if ($slot->isNotEmpty())
            <div class="actions">{{ $slot }}</div>
        @endif
    </div>
</section>
