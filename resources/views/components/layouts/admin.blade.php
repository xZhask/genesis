{{-- Panel admin: mismos colores y tipografía, denso y sin decoración --}}
@props(['title'])

@php
    $nav = [
        'admin.dashboard' => ['Panel', 'admin.dashboard'],
        'admin.admissions.index' => ['Solicitudes', 'admin.admissions.*'],
        'admin.posts.index' => ['Noticias', 'admin.posts.*'],
        'admin.events.index' => ['Eventos', 'admin.events.*'],
        'admin.albums.index' => ['Galería', 'admin.albums.*'],
        'admin.resources.index' => ['Recursos', 'admin.resources.*'],
        'admin.volunteers.index' => ['Apóyanos', ['admin.volunteers.*', 'admin.accounts.*', 'admin.donors.*', 'admin.testimonials.*']],
        'admin.settings.edit' => ['Configuración', 'admin.settings.*'],
    ];
@endphp

<!DOCTYPE html>
<html lang="es-CO">
<head>
    @include('partials.head', [
        'title' => $title.' · Admin',
        'entries' => ['resources/css/admin.css', 'resources/js/admin.js'],
    ])
    <meta name="robots" content="noindex">
</head>
<body class="admin-body">
    <a class="skip-link" href="#contenido">Saltar al contenido</a>

    <header class="admin-bar">
        <div class="admin-wrap admin-bar-inner">
            <a class="admin-brand" href="{{ route('admin.dashboard') }}">
                <img src="{{ asset('images/logo.png') }}" alt="" width="34" height="35">
                <span>Génesis <small>Admin</small></span>
            </a>

            <nav class="admin-nav" aria-label="Panel">
                @foreach ($nav as $route => [$label, $pattern])
                    <a href="{{ route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
                <a href="{{ route('home') }}" target="_blank" rel="noopener">Ver sitio ↗</a>
            </nav>

            <div class="admin-user">
                <button type="button" class="icon-btn theme-btn" data-theme-toggle aria-label="Modo oscuro" aria-pressed="false">
                    <x-icon name="moon" class="moon" />
                    <x-icon name="sun" class="sun" />
                </button>
                <span class="who">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-line btn-sm">Salir</button>
                </form>
            </div>
        </div>
    </header>

    <main id="contenido" class="admin-main" tabindex="-1">
        <div class="admin-wrap">
            @if (session('status_message'))
                <div class="flash" role="status">{{ session('status_message') }}</div>
            @endif

            {{ $slot }}
        </div>
    </main>
</body>
</html>
