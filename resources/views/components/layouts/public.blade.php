@props(['title' => null, 'description' => null, 'demo' => false])

<!DOCTYPE html>
<html lang="es-CO">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    {{-- Aplica el tema antes de pintar para evitar el destello blanco --}}
    <script>
        (function () {
            var t;
            try { t = localStorage.getItem('genesis-theme'); } catch (e) {}
            if (t !== 'light' && t !== 'dark') {
                t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
    <title>{{ $title ? $title.' · ' : '' }}{{ config('school.name') }}</title>
    <meta name="description" content="{{ $description ?? config('school.description') }}">
    <meta name="theme-color" content="#104976">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
