@php
    // Ruta => [texto, rutas que marcan la opción como actual]
    $menu = [
        'home' => ['Inicio', ['home']],
        'about' => ['Nosotros', ['about']],
        'admissions' => ['Admisiones', ['admissions', 'admissions.*']],
        'calendar' => ['Calendario y noticias', ['calendar', 'calendar.*', 'news', 'news.*']],
        'resources' => ['Acudientes', ['resources', 'resources.*']],
        'support' => ['Apóyanos', ['support', 'support.*']],
    ];

    // Con sesión iniciada, "Portal" lleva a la zona de cada rol
    $user = auth()->user();
    $portalUrl = match (true) {
        $user === null => route('login'),
        $user->isAdmin() => route('admin.dashboard'),
        default => route('portal'),
    };
@endphp

<header class="nav">
    <div class="wrap">
        <a class="brand" href="{{ route('home') }}">
            <img src="{{ asset('images/logo.png') }}" alt="" width="50" height="51">
            <span><b>GENESIS</b><small>Centro Educativo Cristiano</small></span>
            <span class="sr-only">, ir al inicio</span>
        </a>

        <nav aria-label="Principal" class="menu-nav">
            <ul class="menu" id="menu">
                @foreach ($menu as $route => [$label, $patterns])
                    <li>
                        <a href="{{ route($route) }}" @if (request()->routeIs(...$patterns)) aria-current="page" @endif>{{ $label }}</a>
                    </li>
                @endforeach
                <li class="menu-portal"><a href="{{ $portalUrl }}">Ingresar al portal</a></li>
            </ul>
        </nav>

        <button type="button" class="icon-btn theme-btn" data-theme-toggle aria-label="Modo oscuro" aria-pressed="false">
            <x-icon name="moon" class="moon" />
            <x-icon name="sun" class="sun" />
        </button>

        <a class="btn btn-azul btn-portal" href="{{ $portalUrl }}">
            <x-icon name="lock" /> Portal
        </a>

        <button type="button" class="icon-btn burger" data-menu-toggle aria-label="Menú" aria-expanded="false" aria-controls="menu">
            <x-icon name="menu" class="open" />
            <x-icon name="close" class="close" />
        </button>
    </div>
</header>
