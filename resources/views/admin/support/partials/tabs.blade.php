@php
    $tabs = [
        'admin.volunteers.index' => ['Voluntarios', 'admin.volunteers.*'],
        'admin.accounts.index' => ['Cuentas para donar', 'admin.accounts.*'],
        'admin.donors.index' => ['Donantes y aliados', 'admin.donors.*'],
        'admin.testimonials.index' => ['Testimonios', 'admin.testimonials.*'],
    ];
@endphp

<nav class="tabs" aria-label="Secciones de Apóyanos">
    @foreach ($tabs as $route => [$label, $pattern])
        <a href="{{ route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
