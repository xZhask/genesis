{{-- Portal de docentes, acudientes y estudiantes: sobrio y funcional, primero para celular --}}
@props(['title'])

@php
    $user = auth()->user();
    $nav = match ($user->role) {
        App\Enums\Role::Teacher => array_filter([
            'portal.teacher.home' => ['Mis clases', 'portal.teacher.home'],
            'portal.teacher.attendance' => ['Asistencia', 'portal.teacher.attendance'],
            'portal.teacher.grades' => ['Notas', 'portal.teacher.grades'],
            'portal.teacher.homeroom' => $user->homeroomSections()->whereHas('schoolYear', fn ($q) => $q->where('is_current', true))->exists()
                ? ['Mi grupo', 'portal.teacher.homeroom'] : null,
            // Un docente que también es acudiente ve a sus acudidos con la misma cuenta
            'portal.guardian.home' => $user->guardian ? ['Mis acudidos', 'portal.guardian.*'] : null,
        ]),
        App\Enums\Role::Guardian => ['portal.guardian.home' => ['Mis acudidos', 'portal.guardian.*']],
        App\Enums\Role::Student => ['portal.student.home' => ['Mi información', 'portal.student.*']],
        default => [],
    };
@endphp

<!DOCTYPE html>
<html lang="es-CO">
<head>
    @include('partials.head', [
        'title' => $title.' · Portal',
        'entries' => ['resources/css/admin.css', 'resources/js/admin.js'],
    ])
    <meta name="robots" content="noindex">
</head>
<body class="admin-body portal-body">
    <a class="skip-link" href="#contenido">Saltar al contenido</a>

    <header class="admin-bar">
        <div class="admin-wrap admin-bar-inner">
            <a class="admin-brand" href="{{ $user->homeUrl() }}">
                <img src="{{ asset('images/logo.png') }}" alt="" width="34" height="35">
                <span>Génesis <small>Portal</small></span>
            </a>

            <nav class="admin-nav" aria-label="Portal">
                @foreach ($nav as $route => [$label, $pattern])
                    <a href="{{ route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
                <a href="{{ route('password.change') }}" @if (request()->routeIs('password.change')) aria-current="page" @endif>Contraseña</a>
            </nav>

            <div class="admin-user">
                <button type="button" class="icon-btn theme-btn" data-theme-toggle aria-label="Modo oscuro" aria-pressed="false">
                    <x-icon name="moon" class="moon" />
                    <x-icon name="sun" class="sun" />
                </button>
                <span class="who">{{ $user->name }}</span>
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
