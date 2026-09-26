<x-layouts.admin :title="'Avance de notas · '.$period->name()">
    <p class="back"><a href="{{ route('admin.academic.years.edit', $year) }}">← Año lectivo {{ $year->year }}</a></p>
    <div class="admin-head row">
        <div>
            <h1>Avance de notas · {{ $period->name() }} {{ $year->year }}</h1>
            <p class="lead-sm">{{ $period->starts_on->longDate() }} – {{ $period->ends_on->longDate() }} · {{ $period->status->label() }}</p>
        </div>
        @unless ($period->isClosed())
            <form method="POST" action="{{ route('admin.academic.periods.close', $period) }}"
                data-confirm="{{ $complete < $total ? 'Faltan '.($total - $complete).' clases por completar. ' : '' }}¿Cerrar el {{ mb_strtolower($period->name()) }}? Sus notas quedarán congeladas y en solo lectura, y las familias podrán verlas.">
                @csrf
                <label class="check notify-check">
                    <input type="checkbox" name="notify" value="1" checked>
                    <span>Avisar a las familias por correo</span>
                </label>
                <button type="submit" class="btn btn-sm btn-danger">Cerrar periodo</button>
            </form>
        @endunless
    </div>

    @include('admin.academic.partials.tabs')

    <ul class="stats progress-stats" aria-label="Resumen">
        <li class="stat {{ $complete === $total ? 'stat-accepted' : 'stat-in_review' }}"><b>{{ $complete }} de {{ $total }}</b><span>clases con notas y logros completos</span></li>
        <li class="stat {{ $behaviorMissing === 0 ? 'stat-accepted' : 'stat-in_review' }}"><b>{{ $behaviorMissing }}</b><span>{{ $behaviorMissing === 1 ? 'estudiante sin comportamiento' : 'estudiantes sin comportamiento' }}</span></li>
    </ul>

    <nav class="filters-inline" aria-label="Filtrar">
        <a href="{{ route('admin.academic.periods.progress', $period) }}" @if ($onlyPending) aria-current="page" @endif>Solo pendientes</a>
        <a href="{{ route('admin.academic.periods.progress', [$period, 'ver' => 'todas']) }}" @unless ($onlyPending) aria-current="page" @endunless>Todas las clases</a>
    </nav>

    @forelse ($sections as $row)
        <section class="panel progress-panel" aria-labelledby="sec-{{ $row['section']->id }}">
            <div class="panel-head">
                <h2 id="sec-{{ $row['section']->id }}">{{ $row['section']->label() }}</h2>
                @if ($period->isClosed())
                    <a class="btn btn-line btn-sm" href="{{ route('report-cards.section', [$row['section'], $period]) }}"><x-icon name="download" /> Boletines</a>
                @endif
                <span class="muted">@if ($row['preschool'])Preescolar · evaluación descriptiva@else Comportamiento: {{ $row['behavior'] }} de {{ $row['students'] }}@endif{{ $row['section']->homeroomTeacher ? ' · '.$row['section']->homeroomTeacher->name : '' }}</span>
            </div>
            @if ($row['classes']->isNotEmpty())
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Materia</th>
                                <th scope="col">Docente</th>
                                <th scope="col">Actividades</th>
                                <th scope="col">Notas</th>
                                <th scope="col">Logros</th>
                                <th scope="col">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($row['classes'] as $class)
                                <tr>
                                    <td data-label="Materia" class="strong">{{ $class['subject'] }}</td>
                                    <td data-label="Docente">{{ $class['teacher'] ?? 'Sin docente' }}</td>
                                    <td data-label="Actividades">
                                        @if ($class['descriptive'])<span class="muted">No aplica</span>@endif
                                        @foreach ($class['components'] as $component => $has)
                                            <span class="comp-chip {{ $has ? 'is-done' : '' }}" title="{{ $has ? 'Con actividades' : 'Sin actividades' }}">{{ App\Enums\EvaluationComponent::from($component)->label() }}</span>
                                        @endforeach
                                    </td>
                                    <td data-label="Notas" class="nowrap">{{ $class['scores'] }} de {{ $class['expected'] }}{{ $class['descriptive'] ? ' descripciones' : '' }}</td>
                                    <td data-label="Logros">{{ $class['objectives'] === null ? 'No aplica' : $class['objectives'].' de 3' }}</td>
                                    <td data-label="Estado"><span class="badge badge-{{ $class['complete'] ? 'accepted' : 'in_review' }}">{{ $class['complete'] ? 'Completa' : 'Pendiente' }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @empty
        <p class="empty panel">{{ $onlyPending ? 'Todo está completo: puedes cerrar el periodo.' : 'No hay clases en este año.' }}</p>
    @endforelse
</x-layouts.admin>
