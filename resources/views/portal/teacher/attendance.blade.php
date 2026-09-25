<x-layouts.portal title="Asistencia">
    <div class="admin-head">
        <h1>Asistencia</h1>
    </div>

    @if ($assignments->isEmpty())
        <p class="empty panel">Todavía no tienes materias asignadas{{ $year ? ' en '.$year->year : '' }}. La administración del colegio las asigna.</p>
    @else
        <form class="attendance-picker panel" method="GET" action="{{ route('portal.teacher.attendance') }}">
            <div class="field">
                <label class="label" for="clase">Clase</label>
                <select id="clase" name="clase" data-autosubmit>
                    @foreach ($assignments as $option)
                        <option value="{{ $option->id }}" @selected($option->is($assignment))>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label" for="fecha">Fecha</label>
                <div class="date-row">
                    <a class="btn btn-line btn-sm" href="{{ route('portal.teacher.attendance', ['clase' => $assignment->id, 'fecha' => $date->copy()->subDay()->toDateString()]) }}" aria-label="Día anterior">‹</a>
                    <input id="fecha" name="fecha" type="date" value="{{ $date->toDateString() }}" min="{{ $year->starts_on->toDateString() }}" max="{{ today()->min($year->ends_on)->toDateString() }}" data-autosubmit>
                    @if ($date->lt(today()->min($year->ends_on)))
                        <a class="btn btn-line btn-sm" href="{{ route('portal.teacher.attendance', ['clase' => $assignment->id, 'fecha' => $date->copy()->addDay()->toDateString()]) }}" aria-label="Día siguiente">›</a>
                    @endif
                </div>
            </div>
            <noscript><button type="submit" class="btn btn-line btn-sm">Ver</button></noscript>
        </form>

        <section class="panel" aria-labelledby="lista-title">
            <h2 id="lista-title">{{ $assignment->label() }}</h2>
            <p class="lead-sm sheet-date">{{ ucfirst($date->translatedFormat('l')) }}, {{ $date->longDate() }}@if ($period) · {{ $period->name() }}@endif</p>

            @error('period')
                <div class="error-summary" role="alert"><p>{{ $message }}</p></div>
            @enderror
            @if ($errors->hasAny(['statuses', 'fecha', 'date']))
                <div class="error-summary" role="alert">
                    <ul>
                        @foreach ($errors->only(['statuses', 'fecha', 'date']) as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! $period)
                <p class="notice">Esa fecha no está dentro de ningún periodo del año lectivo {{ $year->year }}. Elige otra fecha.</p>
            @elseif ($enrollments->isEmpty())
                <p class="empty">No hay estudiantes matriculados en {{ $assignment->section->label() }}.</p>
            @else
                @php($locked = $period->isClosed())
                @if ($locked)
                    <p class="notice">El {{ mb_strtolower($period->name()) }} está cerrado: esta asistencia es de solo lectura. Si hay que corregir algo, pide a la administración que lo reabra.</p>
                @elseif ($lastSaved)
                    <p class="hint">Guardada el {{ $lastSaved->updated_at->longDate() }}, {{ $lastSaved->updated_at->shortTime() }}{{ $lastSaved->recorder ? ' por '.$lastSaved->recorder->name : '' }}. Puedes corregirla y volver a guardar.</p>
                @else
                    <p class="hint">Todos aparecen como presentes: marca solo a quien faltó, llegó tarde o trajo excusa.</p>
                @endif

                <form method="POST" action="{{ route('portal.teacher.attendance.save') }}" data-attendance>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="clase" value="{{ $assignment->id }}">
                    <input type="hidden" name="fecha" value="{{ $date->toDateString() }}">

                    <ol class="attendance-list">
                        @foreach ($enrollments as $enrollment)
                            @php($current = old("statuses.{$enrollment->id}", $records->get($enrollment->id)?->status->value ?? 'present'))
                            <li>
                                <fieldset @disabled($locked)>
                                    <legend><span class="n">{{ $loop->iteration }}.</span> {{ $enrollment->student->sortName() }}</legend>
                                    <div class="att-options">
                                        @foreach ($statuses as $status)
                                            <label class="att-option att-{{ $status->value }}">
                                                <input type="radio" name="statuses[{{ $enrollment->id }}]" value="{{ $status->value }}" @checked($current === $status->value)>
                                                <span aria-hidden="true">{{ $status->short() }}</span>
                                                <span class="sr-only">{{ $status->label() }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            </li>
                        @endforeach
                    </ol>

                    <p class="att-legend">P: presente · A: ausente · T: tarde · E: excusa</p>

                    @unless ($locked)
                        <div class="att-save">
                            <span data-attendance-summary aria-live="polite"></span>
                            <button type="submit" class="btn btn-sol" data-submit data-loading-text="Guardando…"><span>Guardar asistencia</span></button>
                        </div>
                    @endunless
                </form>
            @endif
        </section>
    @endif
</x-layouts.portal>
