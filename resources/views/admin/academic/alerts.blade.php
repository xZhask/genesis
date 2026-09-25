<x-layouts.admin title="Alertas">
    <div class="admin-head">
        <h1>Académico</h1>
        <p class="lead-sm">Estudiantes que necesitan acompañamiento. Las ven los docentes (de sus clases), los directores de grupo y la administración; las familias no.</p>
    </div>

    @include('admin.academic.partials.tabs')

    @if (! $year)
        <p class="empty panel">Primero <a href="{{ route('admin.academic.years.index') }}">crea un año lectivo</a> y márcalo como actual.</p>
    @else
        @if ($section)
            <p class="back"><a href="{{ route('admin.academic.alerts') }}">← Todas las secciones</a></p>
            <h2 class="section-title">{{ $section->label() }}</h2>
            @include('portal.partials.alerts', ['alerts' => $alerts, 'showSection' => false])
        @endif

        <section class="panel" aria-labelledby="resumen-title">
            <h2 id="resumen-title">Resumen por sección</h2>
            <div class="table-wrap">
                <table class="table keep-grid alert-summary">
                    <thead>
                        <tr>
                            <th scope="col">Sección</th>
                            <th scope="col" class="hide-sm">Director de grupo</th>
                            <th scope="col" class="num">Bajo rendimiento</th>
                            <th scope="col" class="num">Inasistencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sections as $option)
                            <tr @if ($section?->is($option)) aria-current="true" class="is-current" @endif>
                                <td><a href="{{ route('admin.academic.alerts', ['seccion' => $option->id]) }}">{{ $option->label() }}</a></td>
                                <td class="hide-sm">{{ $option->homeroomTeacher?->name ?? '—' }}</td>
                                <td class="num">{{ $option->grade->isPreschool() ? '—' : ($low[$option->id] ?? 0) }}</td>
                                <td class="num">{{ $absent[$option->id] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="hint">Número de estudiantes. Preescolar no tiene notas, así que no tiene alerta de rendimiento.</p>
        </section>

        @unless ($section)
            @include('portal.partials.alerts', ['alerts' => $alerts])
        @endunless
    @endif
</x-layouts.admin>
