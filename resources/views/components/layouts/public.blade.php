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

    @if ($demo)
        <div class="demo-note">Contenido de ejemplo para revisar el diseño: noticias, eventos, fotos y cuentas no son reales.</div>
    @endif

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
