{{-- Información de un estudiante: la ve su acudiente o el propio estudiante --}}
@php
    $student = $overview->student;
    $enrollment = $overview->enrollment();
    $year = $overview->year;
    $subjects = $overview->subjects();
    $totals = $overview->totals();
    $isSelf = auth()->user()->student?->is($student);
@endphp

<x-layouts.portal :title="$student->fullName()">
    @if ($siblings->isNotEmpty())
        <nav class="grade-switch" aria-label="Acudidos">
            <a href="{{ route('portal.guardian.student', $student) }}" aria-current="page">{{ $student->first_names }}</a>
            @foreach ($siblings as $sibling)
                <a href="{{ route('portal.guardian.student', $sibling) }}">{{ $sibling->first_names }}</a>
            @endforeach
        </nav>
    @endif

    <div class="admin-head row">
        <div>
            <h1>{{ $isSelf ? 'Hola, '.$student->first_names : $student->fullName() }}</h1>
            <p class="lead-sm">
                @if ($enrollment)
                    {{ $enrollment->section->label() }} · Año lectivo {{ $year->year }}
                    @if ($enrollment->section->homeroomTeacher)
                        · Director de grupo: {{ $enrollment->section->homeroomTeacher->name }}
                    @endif
                @else
                    Sin matrícula en {{ $year?->year ?? 'el año actual' }}
                @endif
            </p>
        </div>
        @if ($enrollment)
            <a class="btn btn-line btn-sm" href="{{ $isSelf ? route('portal.student.schedule') : route('portal.guardian.student.schedule', $student) }}">Ver horario</a>
        @endif
    </div>

    @if ($enrollment?->status === App\Enums\EnrollmentStatus::Withdrawn)
        <p class="notice">Retirado del colegio desde el {{ $enrollment->withdrawn_on?->longDate() }}.</p>
    @endif

    @if ($enrollment)
        <ul class="stats att-stats" aria-label="Asistencia del año">
            <li class="stat"><b>{{ $totals['absent'] }}</b><span>{{ $totals['absent'] === 1 ? 'Falta' : 'Faltas' }}</span></li>
            <li class="stat"><b>{{ $totals['late'] }}</b><span>{{ $totals['late'] === 1 ? 'Llegada tarde' : 'Llegadas tarde' }}</span></li>
            <li class="stat"><b>{{ $totals['excused'] }}</b><span>{{ $totals['excused'] === 1 ? 'Excusa' : 'Excusas' }}</span></li>
        </ul>

        @if (! $overview->hasNumericGrades())
            <section class="panel" aria-labelledby="eval-title">
                <div class="panel-head">
                    <h2 id="eval-title">Evaluación</h2>
                    @if ($overview->closedPeriods()->isNotEmpty())
                        <div class="report-links" aria-label="Informes en PDF">
                            @foreach ($overview->closedPeriods() as $closed)
                                <a class="btn btn-line btn-sm" href="{{ route('report-cards.student', [$student, $closed]) }}" target="_blank" rel="noopener">
                                    <x-icon name="download" /> {{ $closed->number === $year->periods->max('number') ? 'Informe final' : 'Informe P'.$closed->number }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if ($overview->closedPeriods()->isEmpty())
                    <p class="hint">En preescolar la evaluación es descriptiva, sin notas. El informe aparece aquí cuando el colegio cierra cada periodo.</p>
                @else
                    <p class="hint">{{ $overview->closedPeriods()->last()->name() }}: cómo avanza en cada dimensión del desarrollo.</p>
                    <div class="objectives-list">
                        @foreach ($overview->descriptions() as $dimension)
                            <details @if ($loop->first) open @endif>
                                <summary>{{ $dimension['name'] }}</summary>
                                <p>{{ $dimension['text'] ?? 'Sin descripción en este periodo.' }}</p>
                            </details>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        @if ($overview->hasNumericGrades())
            @php
                $scale = $overview->scale();
                $grades = $overview->grades();
                $latest = $overview->closedPeriods()->last();
                $behavior = $overview->behavior();
            @endphp
            <section class="panel" aria-labelledby="notas-title">
                <div class="panel-head">
                    <h2 id="notas-title">Notas</h2>
                    @if ($overview->closedPeriods()->isNotEmpty())
                        <div class="report-links" aria-label="Boletines en PDF">
                            @foreach ($overview->closedPeriods() as $closed)
                                <a class="btn btn-line btn-sm" href="{{ route('report-cards.student', [$student, $closed]) }}" target="_blank" rel="noopener">
                                    <x-icon name="download" /> {{ $closed->number === $year->periods->max('number') ? 'Informe final' : 'Boletín P'.$closed->number }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if ($grades->isEmpty())
                    <p class="hint">Las notas aparecen aquí cuando el colegio cierra cada periodo. Todavía no hay periodos cerrados.</p>
                @else
                    <div class="table-wrap">
                        <table class="table subject-table keep-grid grades-table">
                            <thead>
                                <tr>
                                    <th scope="col">Materia</th>
                                    @foreach ($year->periods as $period)
                                        <th scope="col" class="num"><abbr title="Periodo {{ $period->number }}">P{{ $period->number }}</abbr></th>
                                    @endforeach
                                    <th scope="col" class="num"><abbr title="Acumulado del año">Acum.</abbr></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($grades as $row)
                                    <tr>
                                        <th scope="row">
                                            {{ $row['subject'] }}
                                            @if ($row['status']['key'] === 'reached')
                                                <small class="status-reached"><span aria-hidden="true">✓</span> Ya alcanza para aprobar</small>
                                            @else
                                            <small class="status-{{ $row['status']['key'] }}">
                                                @switch($row['status']['key'])
                                                    @case('needs') Necesita en promedio {{ $scale->format($row['status']['needed']) }} en los periodos que faltan @break
                                                    @case('support') Requiere acompañamiento: habla con el docente @break
                                                    @default No alcanzó la nota para aprobar
                                                @endswitch
                                            </small>
                                            @endif
                                        </th>
                                        @foreach ($row['periods'] as $number => $result)
                                            <td class="num grade-cell perf-{{ $result?->performance?->value }}" data-label="Periodo {{ $number }}"
                                                @if ($result?->performance) title="{{ $result->performance->label() }}" @endif>
                                                {{ $result?->score !== null ? $scale->format($result->score) : '·' }}
                                                @if ($result?->performance)<span class="sr-only"> ({{ $result->performance->label() }})</span>@endif
                                            </td>
                                        @endforeach
                                        <td class="num strong" data-label="Acumulado">{{ $scale->format($row['accumulated']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="hint">Nota para aprobar: {{ $scale->format($scale->passing_score) }}. El acumulado suma cada periodo según su peso. «·» = periodo sin cerrar.
                        Desempeños: <span class="perf-chip perf-low">Bajo</span> <span class="perf-chip perf-basic">Básico</span> <span class="perf-chip perf-high">Alto</span> <span class="perf-chip perf-superior">Superior</span></p>

                    <h3 class="subhead">Logros del {{ mb_strtolower($latest->name()) }}</h3>
                    <div class="objectives-list">
                        @foreach ($grades->filter(fn ($r) => $r['objectives']) as $row)
                            <details>
                                <summary>{{ $row['subject'] }}</summary>
                                <ul>
                                    @foreach ($row['objectives'] as $text)
                                        <li>{{ $text }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endforeach
                    </div>

                    @if ($behavior->isNotEmpty())
                        <h3 class="subhead">Comportamiento</h3>
                        <dl class="data behavior-data">
                            @foreach ($overview->closedPeriods() as $period)
                                @continue(! $behavior->has($period->id))
                                <div>
                                    <dt>{{ $period->name() }}</dt>
                                    <dd>
                                        {{ $scale->format($behavior[$period->id]->behavior) }}
                                        @if ($behavior[$period->id]->observations)
                                            <small>{{ $behavior[$period->id]->observations }}</small>
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                @endif
            </section>
        @endif

        <div class="admin-grid detail">
            <section class="panel" aria-labelledby="materias-title">
                <h2 id="materias-title">Materias e inasistencias</h2>
                @if ($subjects->isEmpty())
                    <p class="hint">El plan de estudios de {{ $enrollment->section->grade->name }} todavía no está cargado.</p>
                @else
                    <div class="table-wrap">
                        <table class="table subject-table keep-grid">
                            <thead>
                                <tr>
                                    <th scope="col">Materia</th>
                                    @foreach ($year->periods as $period)
                                        <th scope="col" class="num"><abbr title="Faltas del periodo {{ $period->number }}">P{{ $period->number }}</abbr></th>
                                    @endforeach
                                    <th scope="col" class="num">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($subjects as $row)
                                    <tr>
                                        <th scope="row">{{ $row['subject'] }} <small>{{ $row['teacher'] ?? 'Docente por asignar' }}</small></th>
                                        @foreach ($row['absences'] as $number => $count)
                                            <td class="num" data-label="Periodo {{ $number }}">{{ $count ?: '·' }}</td>
                                        @endforeach
                                        <td class="num strong" data-label="Total">{{ $row['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="hint">Faltas por materia en cada periodo. El boletín en PDF se publicará aquí más adelante.</p>
                @endif
            </section>

            <section class="panel" aria-labelledby="novedades-title">
                <h2 id="novedades-title">Últimas novedades</h2>
                @forelse ($overview->recentIssues() as $record)
                    <div class="list-row static">
                        <span>
                            <strong>{{ $record->status->label() }}</strong> · {{ $record->subject->name }}
                            <small>{{ ucfirst($record->date->translatedFormat('l')) }}, {{ $record->date->longDate() }}</small>
                        </span>
                        <span class="att-dot att-{{ $record->status->value }}" aria-hidden="true">{{ $record->status->short() }}</span>
                    </div>
                @empty
                    <p class="hint">Sin faltas ni llegadas tarde registradas este año. ¡Muy bien!</p>
                @endforelse
            </section>
        </div>
    @endif
</x-layouts.portal>
