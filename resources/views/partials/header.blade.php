@php
    $menu = [
        'home' => 'Inicio',
        'about' => 'Nosotros',
        'levels' => 'Niveles',
        'admissions' => 'Admisiones',
        'news' => 'Noticias',
        'calendar' => 'Calendario',
        'resources' => 'Acudientes',
        'support' => 'Apóyanos',
    ];
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
                @foreach ($menu as $route => $label)
                    <li>
                        <a href="{{ route($route) }}" @if (request()->routeIs($route, $route.'.*')) aria-current="page" @endif>{{ $label }}</a>
                    </li>
                @endforeach
                <li class="menu-portal"><a href="{{ route('login') }}">Ingresar al portal</a></li>
            </ul>
        </nav>

        <button type="button" class="icon-btn theme-btn" data-theme-toggle aria-label="Modo oscuro" aria-pressed="false">
            <x-icon name="moon" class="moon" />
            <x-icon name="sun" class="sun" />
        </button>

        <a class="btn btn-azul btn-portal" href="{{ route('login') }}">
            <x-icon name="lock" /> Portal
        </a>

        <button type="button" class="icon-btn burger" data-menu-toggle aria-label="Menú" aria-expanded="false" aria-controls="menu">
            <x-icon name="menu" class="open" />
            <x-icon name="close" class="close" />
        </button>
    </div>
</header>
