@props(['title' => null, 'description' => null, 'demo' => false])

<!DOCTYPE html>
<html lang="es-CO">
<head>
    @include('partials.head', [
        'title' => $title,
        'description' => $description ?? config('school.description'),
        'entries' => ['resources/css/app.css', 'resources/js/app.js'],
    ])
</head>
<body @class(['has-whatsapp' => config('school.whatsapp.enabled')])>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>

    {{--
        Franja superior desactivada. Se conserva (con su estilo .demo-note) por si
        se necesita para un anuncio de suma importancia, por ejemplo:
        <div class="demo-note">Mañana no habrá clases por la jornada electoral.</div>
    --}}

    @include('partials.topbar')
    @include('partials.header')

    <main id="contenido" tabindex="-1">
        {{ $slot }}
    </main>

    @include('partials.footer')
    @include('partials.whatsapp')

    <div class="toast" data-toast role="status" aria-live="polite"></div>
</body>
</html>
