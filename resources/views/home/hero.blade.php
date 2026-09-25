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
                <a class="btn btn-sol" href="{{ route('admissions') }}"><x-icon name="form" /> Solicitar matrícula</a>
                <a class="btn btn-line" href="{{ route('login') }}"><x-icon name="lock" /> Ingresar al portal</a>
            </div>
            <p class="motto">{{ config('school.motto') }}</p>
        </div>

        <div class="crest" aria-hidden="true">
            <div class="shield">
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
            <svg class="book" viewBox="0 0 400 40" focusable="false">
                <path d="M6 6 C90 34 170 34 200 22 C230 34 310 34 394 6 L394 16 C310 42 230 42 200 32 C170 42 90 42 6 16 Z" fill="#3FA64A" />
            </svg>
        </div>
    </div>
</section>
