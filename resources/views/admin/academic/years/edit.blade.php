<x-layouts.admin :title="'Año lectivo '.$year->year">
    <p class="back"><a href="{{ route('admin.academic.years.index') }}">← Años lectivos</a></p>
    <div class="admin-head row">
        <div>
            <h1>Año lectivo {{ $year->year }}</h1>
            <p class="lead-sm">{{ $year->starts_on->longDate() }} – {{ $year->ends_on->longDate() }}</p>
        </div>
        @if ($year->is_current)
            <span class="badge badge-lg badge-accepted">Año actual del portal</span>
        @else
            <form method="POST" action="{{ route('admin.academic.years.current', $year) }}" data-confirm="¿Usar {{ $year->year }} como año actual? El portal mostrará sus secciones y periodos.">
                @csrf
                <button type="submit" class="btn btn-line btn-sm">Marcar como año actual</button>
            </form>
        @endif
    </div>

    @include('admin.academic.partials.tabs')

    <div class="admin-grid detail">
        <div class="stack">
            <section class="panel" aria-labelledby="periodos-title">
                <h2 id="periodos-title">Periodos</h2>
                <form class="form compact" method="POST" action="{{ route('admin.academic.periods.update', $year) }}" novalidate data-form>
                    @csrf
                    @method('PUT')
                    <div class="table-wrap">
                        <table class="table period-table">
                            <thead>
                                <tr>
                                    <th scope="col">Periodo</th>
                                    <th scope="col">Inicio</th>
                                    <th scope="col">Fin</th>
                                    <th scope="col">Peso (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($year->periods as $i => $period)
                                    <tr>
                                        <th scope="row">{{ $period->number }}</th>
                                        <td data-label="Inicio">
                                            <input type="date" name="periods[{{ $i }}][starts_on]" aria-label="Inicio del periodo {{ $period->number }}"
                                                value="{{ old("periods.{$i}.starts_on", $period->starts_on->toDateString()) }}">
                                        </td>
                                        <td data-label="Fin">
                                            <input type="date" name="periods[{{ $i }}][ends_on]" aria-label="Fin del periodo {{ $period->number }}"
                                                value="{{ old("periods.{$i}.ends_on", $period->ends_on->toDateString()) }}">
                                        </td>
                                        <td data-label="Peso (%)">
                                            <input type="number" step="0.01" min="1" max="100" inputmode="decimal" name="periods[{{ $i }}][weight]" class="w-num"
                                                aria-label="Peso del periodo {{ $period->number }}" value="{{ old("periods.{$i}.weight", (float) $period->weight) }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="hint">Los pesos deben sumar 100 %. Provisional: {{ config('school.academic.periods') }} periodos de igual peso, pendiente de confirmar con el SIEE.</p>
                    <button type="submit" class="btn btn-azul" data-submit data-loading-text="Guardando…"><span>Guardar periodos</span></button>
                </form>
            </section>

            <section class="panel" aria-labelledby="escala-title">
                <h2 id="escala-title">Escala de valoración</h2>
                <p class="hint">Nota mínima, máxima y aprobatoria, rangos de Bajo, Básico, Alto y Superior, frases de los logros y pesos de saber, hacer y ser.</p>
                <a class="btn btn-line btn-sm" href="{{ route('admin.academic.scale.edit', $year) }}">Ver y editar la escala</a>
            </section>

            <section class="panel" aria-labelledby="fechas-title">
                <h2 id="fechas-title">Fechas del año</h2>
                <form class="form compact" method="POST" action="{{ route('admin.academic.years.update', $year) }}" novalidate data-form>
                    @csrf
                    @method('PUT')
                    <div class="field-group even">
                        <x-field name="starts_on" label="Inicio de clases">
                            <input id="starts_on" name="starts_on" type="date" value="{{ old('starts_on', $year->starts_on->toDateString()) }}">
                        </x-field>
                        <x-field name="ends_on" label="Fin de clases">
                            <input id="ends_on" name="ends_on" type="date" value="{{ old('ends_on', $year->ends_on->toDateString()) }}">
                        </x-field>
                    </div>
                    <button type="submit" class="btn btn-line" data-submit><span>Guardar fechas</span></button>
                </form>
            </section>
        </div>

        <section class="panel" aria-labelledby="cierre-title">
            <h2 id="cierre-title">Cierre de periodos</h2>
            <p class="hint">Un periodo cerrado queda en solo lectura: nadie puede modificar sus notas ni su asistencia. Solo el admin puede reabrirlo y queda registrado.</p>

            <ul class="period-states">
                @foreach ($year->periods as $period)
                    <li>
                        <div class="period-state-head">
                            <strong>{{ $period->name() }}</strong>
                            <span class="badge badge-{{ $period->isClosed() ? 'withdrawn' : 'accepted' }}">{{ $period->status->label() }}</span>
                            <a class="btn-link small-link" href="{{ route('admin.academic.periods.progress', $period) }}">Avance de notas</a>
                            @if ($period->isClosed())
                                <form method="POST" action="{{ route('admin.academic.periods.reopen', $period) }}"
                                    data-confirm="¿Reabrir el {{ mb_strtolower($period->name()) }}? Los docentes podrán volver a modificar notas y asistencia.">
                                    @csrf
                                    <button type="submit" class="btn btn-line btn-sm">Reabrir</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.academic.periods.close', $period) }}"
                                    data-confirm="¿Cerrar el {{ mb_strtolower($period->name()) }}? Sus notas y asistencia quedarán en solo lectura.">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger">Cerrar</button>
                                </form>
                            @endif
                        </div>
                        @if ($period->statusChanges->isNotEmpty())
                            <ul class="period-history">
                                @foreach ($period->statusChanges as $change)
                                    <li>{{ $change->status === App\Enums\PeriodStatus::Closed ? 'Cerrado' : 'Reabierto' }} el {{ $change->created_at->longDate() }}, {{ $change->created_at->shortTime() }} · {{ $change->user?->name ?? 'usuario eliminado' }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</x-layouts.admin>
