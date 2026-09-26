@use('App\Support\Indicators')

<x-layouts.admin title="Indicadores">
    @push('vite')
        @vite('resources/js/charts.js')
    @endpush

    <div class="admin-head">
        <h1>Académico</h1>
        <p class="lead-sm">Asistencia y aprobación del año. Estas cifras por sección y grado solo las ve la administración.</p>
    </div>

    @include('admin.academic.partials.tabs')

    @if (! $year)
        <p class="empty panel">Primero <a href="{{ route('admin.academic.years.index') }}">crea un año lectivo</a> y márcalo como actual.</p>
    @else
        @php
            $monthName = $month->translatedFormat('F \d\e Y');
            $lowest = $sections->sortBy('percent')->first();
            $barData = [
                'type' => 'bar',
                'unit' => '%',
                'label' => 'asistencia',
                'labels' => $sections->map(fn ($r) => $r['section']->label())->all(),
                'values' => $sections->pluck('percent')->all(),
            ];
        @endphp

        <form class="filter-row" method="GET" action="{{ route('admin.academic.indicators') }}">
            <label for="mes">Mes de la asistencia</label>
            <select id="mes" name="mes" data-autosubmit>
                @foreach ($months as $option)
                    <option value="{{ $option->format('Y-m') }}" @selected($option->equalTo($month))>{{ ucfirst($option->translatedFormat('F \d\e Y')) }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="btn btn-line btn-sm">Ver</button></noscript>
        </form>

        <div class="charts-grid">
            @if ($sections->isEmpty())
                <section class="panel chart-card">
                    <h2>Asistencia por sección</h2>
                    <p class="empty">No hay asistencia registrada en {{ $monthName }}.</p>
                </section>
            @else
                <x-chart title="Asistencia por sección"
                    :summary="'Asistencia de '.$monthName.' en todo el colegio: '.Indicators::percent($monthTotal).'. La más baja fue la de '.$lowest['section']->label().' ('.Indicators::percent($lowest['percent']).').'"
                    :data="$barData" :height="max(180, 30 * $sections->count() + 50)">
                    <table class="table keep-grid">
                        <thead><tr><th scope="col">Sección</th><th scope="col" class="num">Asistencia</th><th scope="col" class="num">Registros</th></tr></thead>
                        <tbody>
                            @foreach ($sections as $row)
                                <tr>
                                    <td>{{ $row['section']->label() }}</td>
                                    <td class="num">{{ Indicators::percent($row['percent']) }}</td>
                                    <td class="num">{{ $row['attended'] }} de {{ $row['total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-chart>
            @endif

            <section class="panel chart-card" aria-labelledby="aprobacion-title">
                <h2 id="aprobacion-title">Aprobación por grado y periodo</h2>
                @if ($approval['periods']->isEmpty() || $approval['rows']->isEmpty())
                    <p class="empty">Aparece cuando se cierra el primer periodo: se calcula con las notas guardadas al cerrarlo.</p>
                @else
                    <p class="chart-summary">Porcentaje de notas de materia aprobadas en cada periodo cerrado; más oscuro, más aprobación (escala: menos de 60, 60–80, 80–90, 90–95 y 95 % o más). Preescolar no tiene notas.</p>
                    <div class="table-wrap">
                        <table class="table keep-grid heat">
                            <thead>
                                <tr>
                                    <th scope="col">Grado</th>
                                    @foreach ($approval['periods'] as $period)
                                        <th scope="col" class="num">{{ $period->name() }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($approval['rows'] as $row)
                                    <tr>
                                        <th scope="row">{{ $row['grade'] }}</th>
                                        @foreach ($approval['periods'] as $period)
                                            @php
                                                $cell = $row['cells'][$period->id];
                                            @endphp
                                            <td class="num" @if ($cell) data-step="{{ Indicators::step($cell['percent']) }}" title="{{ $cell['total'] }} notas" @endif>
                                                {{ $cell ? Indicators::percent($cell['percent']) : '—' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="heat-legend" aria-hidden="true">
                        <span>Menos de 60 %</span>
                        @foreach ([1, 2, 3, 4, 5] as $step)
                            <i class="heat-swatch" data-step="{{ $step }}"></i>
                        @endforeach
                        <span>95 % o más</span>
                    </div>
                @endif
            </section>
        </div>
    @endif
</x-layouts.admin>
