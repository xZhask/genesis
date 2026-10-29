@php
    // Sin fotos reales, el escudo muestra los símbolos del logo
    $symbols = [
        ['tree', 't-verde'],
        ['puzzle', 't-sol'],
        ['globe', 't-azul'],
        ['bible', 't-navy'],
    ];
@endphp

<section class="hero" aria-labelledby="hero-title">
    <div class="wrap">
        <div class="txt">
            @if ($badge = config('school.admissions_badge'))
                <span class="chip"><i></i>{{ $badge }}</span>
            @endif
            <h1 id="hero-title">Aquí se aprende con alegría y se crece con propósito</h1>
            <p class="lead">
                Preescolar, básica primaria y básica secundaria en Zambrano, Bolívar. Formación académica con
                valores cristianos, inglés fortalecido y acompañamiento cercano a cada familia.
            </p>
            <div class="ctas">
                <a class="btn btn-sol" href="{{ route('admissions') }}#solicitud"><x-icon name="form" /> Solicitar matrícula</a>
                <a class="btn btn-line" href="{{ route('login') }}"><x-icon name="lock" /> Ingresar al portal</a>
            </div>
            <p class="motto">{{ config('school.motto') }}</p>
        </div>

        <div class="crest" aria-hidden="true">
            @if ($heroImage)
                <img class="crest-img" src="{{ asset($heroImage) }}" alt="" width="880" height="972" fetchpriority="high">
            @else
                <div class="shield">
                    <div class="frame">
                        <div class="quad">
                            @if (count($heroPhotos) === 4)
                                @foreach ($heroPhotos as $photo)
                                    <x-photo :src="$photo" />
                                @endforeach
                            @else
                                @foreach ($symbols as [$icon, $tone])
                                    <x-photo :tone="$tone" :icon="$icon" />
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            @endif
            {{-- Libro abierto del logo: dos páginas arqueadas hacia arriba --}}
            <svg class="book" viewBox="0 0 400 52" focusable="false">
                <path d="M0 9 C60 0 140 -2 200 22 C260 -2 340 0 400 9 L393 37 C330 26 260 26 200 51 C140 26 70 26 7 37 Z" fill="#3FA64A" />
            </svg>
        </div>
    </div>
</section>
