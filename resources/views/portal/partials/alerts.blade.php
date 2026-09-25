{{-- Alertas para docentes y administración: cumpleaños, bajo rendimiento e inasistencia. Las familias no las ven. --}}
@php
    $birthdays = $alerts->birthdays();
    $low = $alerts->lowPerformance();
    $absences = $alerts->absences();
    $scale = $alerts->scale();
    $threshold = (int) config('school.alerts.absences');
    $showSection ??= true;
    // Una fila por estudiante, con sus materias
    $lowByStudent = $low->groupBy(fn ($row) => $row['enrollment']->id);
    $absentByStudent = $absences->groupBy(fn ($row) => $row['enrollment']->id);
@endphp

<section class="alerts" aria-labelledby="alertas-title">
    <h2 id="alertas-title" class="section-title">
        Alertas @if ($alerts->period)<small>· {{ $alerts->period->name() }}</small>@endif
    </h2>

    <div class="alert-grid">
        @if ($alerts->hasNumericGrades())
        <section class="panel alert-panel alert-low" aria-labelledby="bajo-title">
            <h3 id="bajo-title">Bajo rendimiento <span class="alert-count">{{ $lowByStudent->count() }}</span></h3>
            @forelse ($lowByStudent as $rows)
                @php
                    $enrollment = $rows->first()['enrollment'];
                @endphp
                <div class="alert-row">
                    <span>
                        <strong>{{ $enrollment->student->sortName() }}</strong>
                        @if ($showSection)<small>{{ $enrollment->section->label() }}</small>@endif
                        <ul class="alert-subjects">
                            @foreach ($rows as $row)
                                <li>
                                    {{ $row['subject']->name }}
                                    @if ($row['score'] !== null)<b title="Nota del periodo hasta hoy">{{ $scale->format($row['score']) }}</b>@endif
                                    @if ($row['year'])<em>ya no alcanza el año</em>@endif
                                </li>
                            @endforeach
                        </ul>
                    </span>
                </div>
            @empty
                <p class="muted">Nadie está por debajo de {{ $scale->format($scale->passing_score) }} en lo que va del periodo.</p>
            @endforelse
            <p class="hint">Nota del periodo hasta hoy, con al menos {{ config('school.alerts.min_graded_items') }} actividades calificadas.</p>
        </section>
        @endif

        <section class="panel alert-panel alert-absent" aria-labelledby="faltas-title">
            <h3 id="faltas-title">Inasistencia <span class="alert-count">{{ $absentByStudent->count() }}</span></h3>
            @forelse ($absentByStudent as $rows)
                @php
                    $enrollment = $rows->first()['enrollment'];
                @endphp
                <div class="alert-row">
                    <span>
                        <strong>{{ $enrollment->student->sortName() }}</strong>
                        @if ($showSection)<small>{{ $enrollment->section->label() }}</small>@endif
                        <ul class="alert-subjects">
                            @foreach ($rows as $row)
                                <li>{{ $row['subject']->name }} <b>{{ trans_choice(':count falta|:count faltas', $row['count']) }}</b></li>
                            @endforeach
                        </ul>
                    </span>
                </div>
            @empty
                <p class="muted">Nadie tiene {{ $threshold }} o más faltas sin excusa en una materia este periodo.</p>
            @endforelse
            <p class="hint">Faltas sin excusa por materia en el periodo (desde {{ $threshold }}).</p>
        </section>

        <section class="panel alert-panel alert-birthday" aria-labelledby="cumple-title">
            <h3 id="cumple-title">Cumpleaños de la semana <span class="alert-count">{{ $birthdays->count() }}</span></h3>
            @forelse ($birthdays as $row)
                <p class="alert-row">
                    <span>
                        <strong>{{ $row['enrollment']->student->fullName() }}</strong>
                        @if ($showSection)<small>{{ $row['enrollment']->section->label() }}</small>@endif
                    </span>
                    <span class="alert-value">
                        @if ($row['days'] === 0)
                            <b class="is-today">¡Hoy!</b>
                        @elseif ($row['days'] === 1)
                            <b>Mañana</b>
                        @else
                            <b>{{ ucfirst($row['date']->translatedFormat('l j')) }}</b>
                        @endif
                    </span>
                </p>
            @empty
                <p class="muted">No hay cumpleaños en los próximos {{ config('school.alerts.birthday_days') }} días.</p>
            @endforelse
        </section>
    </div>
</section>
