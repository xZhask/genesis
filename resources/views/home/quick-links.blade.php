@php
    $links = [
        [route('calendar'), 'calendar', 'azul', 'Calendario escolar', 'Eventos y fechas clave'],
        [route('resources'), 'download', 'verde', 'Recursos para acudientes', 'Circulares y documentos'],
        [route('resources').'#uniformes', 'shirt', 'sol', 'Uniformes y listas', 'Especificaciones y proveedores'],
        [route('resources').'#horarios', 'clock', 'navy', 'Horarios', 'Por grado y jornada'],
    ];
@endphp

<nav class="quick" aria-label="Accesos rápidos">
    <div class="wrap">
        <div class="grid">
            @foreach ($links as [$url, $icon, $color, $title, $hint])
                <a href="{{ $url }}">
                    <span class="ic ic-{{ $color }}"><x-icon :name="$icon" /></span>
                    <span><strong>{{ $title }}</strong><small>{{ $hint }}</small></span>
                </a>
            @endforeach
        </div>
    </div>
</nav>
