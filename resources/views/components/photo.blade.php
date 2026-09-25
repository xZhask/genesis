{{-- Foto real o, si no hay, un fondo de color con icono (estilo del mockup) --}}
@props(['src' => null, 'tone' => 't-azul', 'icon' => null, 'label' => null, 'alt' => ''])

@if ($src)
    <img {{ $attributes->class('media') }} src="{{ str_starts_with($src, 'http') ? $src : asset($src) }}" alt="{{ $alt }}" loading="lazy" decoding="async">
@else
    <div {{ $attributes->class(['media', 'ph', $tone]) }} @unless ($label) aria-hidden="true" @endunless>
        <div class="in">
            @if ($icon)
                <x-icon :name="$icon" />
            @endif
            @if ($label)
                <span>{{ $label }}</span>
            @endif
        </div>
    </div>
@endif
