{{-- Inicio de sesión y recuperación de contraseña: sobrio, centrado, primero para celular --}}
@props(['title'])

<!DOCTYPE html>
<html lang="es-CO">
<head>
    @include('partials.head', [
        'title' => $title,
        'entries' => ['resources/css/admin.css', 'resources/js/admin.js'],
    ])
    <meta name="robots" content="noindex">
</head>
<body class="auth-body">
    <main class="auth" id="contenido">
        <a class="auth-brand" href="{{ route('home') }}">
            <img src="{{ asset('images/logo.png') }}" alt="" width="56" height="57">
            <span><b>GENESIS</b><small>Centro Educativo Cristiano</small></span>
        </a>

        <div class="auth-card">
            {{ $slot }}
        </div>

        <p class="auth-foot">
            <a href="{{ route('home') }}">← Volver al sitio del colegio</a>
        </p>
    </main>

    <button type="button" class="icon-btn theme-btn theme-float" data-theme-toggle aria-label="Modo oscuro" aria-pressed="false">
        <x-icon name="moon" class="moon" />
        <x-icon name="sun" class="sun" />
    </button>
</body>
</html>
