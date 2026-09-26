@use('App\Support\Indicators')

<x-layouts.portal title="Resumen">
    @push('vite')
        @vite('resources/js/charts.js')
    @endpush

    <div class="admin-head">
        <h1>Resumen de mis clases</h1>
        <p class="lead-sm">Asistencia del mes y cómo van las notas del periodo. Solo tú y la administración ven estas cifras.</p>
    </div>

    @if (! $year || $classes->isEmpty())
        <p class="empty panel">Cuando tengas clases asignadas este año, aquí verás su resumen.</p>
    @else
        @php
            $monthName = $month->translatedFormat('F \d\e Y');
            $lowest = $days->sortBy('percent')->first();
            $lineData = [
                'type' => 'line',
                'unit' => '%',
                'label' => 'asistencia',
                'labels' => $days->map(fn ($d) => ucfirst($d['date']->translatedFormat('D j')))->all(),
                'values' => $days->pluck('percent')->all(),
                'details' => $days->map(fn ($d) => "{$d['attended']} de {$d['total']} registros")->all(),
            ];
            $totals = ['passed' => $passFail->sum('passed'), 'failed' => $passFail->sum('failed'), 'pending' => $passFail->sum('pending')];
            $stackData = [
                'type' => 'stack',
                'labels' => $passFail->map(fn ($r) => $r['class']->section->label().' · '.$r['class']->subject->name)->all(),
                'series' => [
                    ['label' => 'Aprueban', 'color' => '1', 'values' => $passFail->pluck('passed')->all()],
                    ['label' => 'No aprueban', 'color' => '2', 'values' => $passFail->pluck('failed')->all()],
                    ['label' => 'Sin nota', 'color' => 'neutral', 'values' => $passFail->pluck('pending')->all()],
                ],
            ];
        @endphp

        <form class="filter-row" method="GET" action="{{ route('portal.teacher.summary') }}">
            <label for="mes">Mes de la asistencia</label>
            <select id="mes" name="mes" data-autosubmit>
                @foreach ($months as $option)
                    <option value="{{ $option->format('Y-m') }}" @selected($option->equalTo($month))>{{ ucfirst($option->translatedFormat('F \d\e Y')) }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="btn btn-line btn-sm">Ver</button></noscript>
        </form>

        <div class="kpi-row">
            <div class="kpi"><span>Asistencia en {{ $month->translatedFormat('F') }}</span><b>{{ Indicators::percent($monthTotal) }}</b><small>Presentes y llegadas tarde</small></div>
            <div class="kpi"><span>En bajo rendimiento</span><b>{{ $lowStudents }}</b><small>{{ $period?->name() }}, nota hasta hoy</small></div>
            <div class="kpi"><span>Con inasistencia</span><b>{{ $absentStudents }}</b><small>{{ config('school.alerts.absences') }}+ faltas sin excusa en una materia</small></div>
        </div>

        <div class="charts-grid">
            @if ($days->isEmpty())
                <section class="panel chart-card">
                    <h2>Asistencia por día</h2>
                    <p class="empty">No hay asistencia registrada en {{ $monthName }}.</p>
                </section>
            @else
                <x-chart title="Asistencia por día"
                    :summary="'Asistencia de '.$monthName.' en tus clases: '.Indicators::percent($monthTotal).'. El día más bajo fue el '.$lowest['date']->dayMonth().' ('.Indicators::percent($lowest['percent']).').'"
                    :data="$lineData">
                    <table class="table keep-grid">
                        <thead><tr><th scope="col">Día</th><th scope="col" class="num">Asistencia</th><th scope="col" class="num">Registros</th></tr></thead>
                        <tbody>
                            @foreach ($days as $day)
                                <tr>
                                    <td>{{ ucfirst($day['date']->translatedFormat('l j')) }}</td>
                                    <td class="num">{{ Indicators::percent($day['percent']) }}</td>
                                    <td class="num">{{ $day['attended'] }} de {{ $day['total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-chart>
            @endif

            @if ($passFail->isNotEmpty())
                <x-chart :title="'Notas del '.mb_strtolower($period->name())"
                    :summary="'Nota hasta hoy de cada estudiante en cada clase. En total: '.$totals['passed'].' '.($totals['passed'] === 1 ? 'aprueba' : 'aprueban').', '.$totals['failed'].' no '.($totals['failed'] === 1 ? 'aprueba' : 'aprueban').' y '.$totals['pending'].' sin nota.'"
                    :data="$stackData" :height="max(160, 38 * $passFail->count() + 50)"
                    :legend="[['Aprueban', '1'], ['No aprueban', '2'], ['Sin nota', 'neutral']]">
                    <table class="table keep-grid">
                        <thead><tr><th scope="col">Clase</th><th scope="col" class="num">Aprueban</th><th scope="col" class="num">No aprueban</th><th scope="col" class="num">Sin nota</th></tr></thead>
                        <tbody>
                            @foreach ($passFail as $row)
                                <tr>
                                    <td>{{ $row['class']->section->label() }} · {{ $row['class']->subject->name }}</td>
                                    <td class="num">{{ $row['passed'] }}</td>
                                    <td class="num">{{ $row['failed'] }}</td>
                                    <td class="num">{{ $row['pending'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-chart>
            @endif
        </div>
    @endif
</x-layouts.portal>
