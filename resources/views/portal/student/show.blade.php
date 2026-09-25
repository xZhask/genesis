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

    <div class="admin-head">
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

    @if ($enrollment?->status === App\Enums\EnrollmentStatus::Withdrawn)
        <p class="notice">Retirado del colegio desde el {{ $enrollment->withdrawn_on?->longDate() }}.</p>
    @endif

    @if ($enrollment)
        <ul class="stats att-stats" aria-label="Asistencia del año">
            <li class="stat"><b>{{ $totals['absent'] }}</b><span>{{ $totals['absent'] === 1 ? 'Falta' : 'Faltas' }}</span></li>
            <li class="stat"><b>{{ $totals['late'] }}</b><span>{{ $totals['late'] === 1 ? 'Llegada tarde' : 'Llegadas tarde' }}</span></li>
            <li class="stat"><b>{{ $totals['excused'] }}</b><span>{{ $totals['excused'] === 1 ? 'Excusa' : 'Excusas' }}</span></li>
        </ul>

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
                    <p class="hint">Faltas por materia en cada periodo. Las notas y el boletín se publicarán aquí más adelante.</p>
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
