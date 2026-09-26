{{--
    Gráfico con Chart.js: título, una frase que lo resume (también es el
    texto alternativo), leyenda (dos o más series) y la tabla con los mismos
    datos, que se ve sin JavaScript y sirve para lectores de pantalla.
--}}
@props(['title', 'summary', 'data', 'legend' => [], 'height' => 260])

<section {{ $attributes->class(['panel', 'chart-card']) }}>
    <h2>{{ $title }}</h2>
    <p class="chart-summary">{{ $summary }}</p>

    <figure class="chart" data-chart style="margin: 0">
        <div class="chart-box" style="--chart-h: {{ $height }}px">
            <canvas role="img" aria-label="{{ $title }}. {{ $summary }}"></canvas>
        </div>
        <script type="application/json">@json($data)</script>
        @if (count($legend) > 1)
            <ul class="chart-legend">
                @foreach ($legend as [$label, $color])
                    <li><i style="--swatch: var(--chart-{{ $color }})" aria-hidden="true"></i>{{ $label }}</li>
                @endforeach
            </ul>
        @endif
    </figure>

    <details class="chart-table">
        <summary>Ver los datos en una tabla</summary>
        {{ $slot }}
    </details>
</section>
