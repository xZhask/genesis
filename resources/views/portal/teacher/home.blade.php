<x-layouts.portal title="Mis clases">
    <div class="admin-head">
        <h1>Hola, {{ Str::before(auth()->user()->name, ' ') }}</h1>
        <p class="lead-sm">
            {{ ucfirst(today()->translatedFormat('l')) }}, {{ today()->longDate() }}
            @if ($period)
                · {{ $period->name() }} {{ $period->isClosed() ? '(cerrado)' : '' }}
            @endif
        </p>
    </div>

    @if (! $year)
        <p class="empty panel">El colegio todavía no ha configurado el año lectivo. Cuando lo haga, aquí verás tus clases.</p>
    @elseif ($assignments->isEmpty())
        <p class="empty panel">Todavía no tienes materias asignadas en {{ $year->year }}. La administración del colegio las asigna.</p>
    @else
        <section aria-labelledby="clases-title">
            <h2 id="clases-title" class="sr-only">Mis clases de {{ $year->year }}</h2>
            <ul class="class-grid">
                @foreach ($assignments as $assignment)
                    @php($done = in_array($assignment->section_id.'|'.$assignment->subject_id, $taken, true))
                    <li class="class-card">
                        <p class="class-section">{{ $assignment->section->label() }}</p>
                        <h3>{{ $assignment->subject->name }}</h3>
                        <p class="class-status {{ $done ? 'is-done' : '' }}">{{ $done ? '✓ Asistencia de hoy tomada' : 'Asistencia de hoy pendiente' }}</p>
                        <a class="btn btn-sm {{ $done ? 'btn-line' : 'btn-azul' }}" href="{{ route('portal.teacher.attendance', ['clase' => $assignment->id]) }}">
                            {{ $done ? 'Revisar asistencia' : 'Tomar asistencia' }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($homerooms->isNotEmpty())
        <section class="panel" aria-labelledby="grupo-title">
            <h2 id="grupo-title">Director de grupo</h2>
            @foreach ($homerooms as $section)
                <p>{{ $section->label() }} · {{ trans_choice(':count estudiante|:count estudiantes', $section->enrollments_count) }}</p>
            @endforeach
        </section>
    @endif
</x-layouts.portal>
