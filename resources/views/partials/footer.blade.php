@php
    $contact = config('school.contact');
    $hours = config('school.office_hours');
@endphp

<footer class="site-footer on-dark">
    <div class="wrap">
        <div @class(['grid', 'with-hours' => $hours])>
            <div>
                <div class="fbrand">
                    <img src="{{ asset('images/logo.png') }}" alt="" width="56" height="57" loading="lazy">
                    <span><b>GENESIS</b><small>Centro Educativo Cristiano</small></span>
                </div>
                <p class="fdesc">{{ config('school.motto') }}. Preescolar, básica primaria y básica secundaria.</p>
                <a class="social" href="{{ config('school.social.facebook') }}" target="_blank" rel="noopener">
                    <x-icon name="facebook" /> Síguenos en Facebook
                </a>
            </div>

            @if ($hours)
                <div>
                    <h2>Horarios</h2>
                    <ul>
                        @foreach ($hours as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <nav aria-label="Enlaces del pie de página">
                <h2>Enlaces</h2>
                <ul>
                    <li><a href="{{ route('about') }}">Nosotros</a></li>
                    <li><a href="{{ route('admissions') }}">Admisiones</a></li>
                    <li><a href="{{ route('calendar') }}">Calendario escolar</a></li>
                    <li><a href="{{ route('resources') }}">Recursos para acudientes</a></li>
                    <li><a href="{{ route('support') }}">Apóyanos</a></li>
                    <li><a href="{{ route('login') }}">Portal de acudientes</a></li>
                </ul>
            </nav>

            <div>
                <h2>Visítanos</h2>
                <ul class="contact">
                    <li>{{ $contact['address'] }}, {{ $contact['city'] }}</li>
                    <li><a href="tel:{{ $contact['phone_link'] }}">{{ $contact['phone'] }}</a></li>
                    <li><a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></li>
                </ul>
                <div class="map">
                    <span class="pin" aria-hidden="true"><x-icon name="pin" /></span>
                    <a href="{{ $contact['maps_url'] }}" target="_blank" rel="noopener">Cómo llegar</a>
                </div>
            </div>
        </div>

        <div class="copyline">
            <span>© {{ now()->year }} {{ config('school.name') }}</span>
            <a href="{{ route('privacy') }}">Política de tratamiento de datos (Ley 1581 de 2012)</a>
        </div>
    </div>
</footer>
