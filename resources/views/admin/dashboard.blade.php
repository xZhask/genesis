@use('App\Enums\AdmissionStatus')
@php
    $cards = [
        [AdmissionStatus::Received, 'Por revisar'],
        [AdmissionStatus::InReview, 'En revisión'],
        [AdmissionStatus::InterviewScheduled, 'Con entrevista'],
        [AdmissionStatus::Accepted, 'Aceptadas'],
    ];
@endphp

<x-layouts.admin title="Panel">
    <div class="admin-head">
        <h1>Hola, {{ auth()->user()->name }}</h1>
        @if ($pending)
            <p class="lead-sm">Tienes <strong>{{ $pending }} {{ $pending === 1 ? 'solicitud nueva' : 'solicitudes nuevas' }}</strong> por revisar.</p>
        @else
            <p class="lead-sm">No hay solicitudes nuevas por revisar.</p>
        @endif
    </div>

    <section aria-labelledby="resumen-title">
        <h2 id="resumen-title" class="sr-only">Resumen de pre-inscripciones</h2>
        <div class="stats">
            @foreach ($cards as [$status, $label])
                <a class="stat stat-{{ $status->value }}" href="{{ route('admin.admissions.index', ['estado' => $status->value]) }}">
                    <b>{{ $counts[$status->value] ?? 0 }}</b>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <div class="admin-grid">
        <section class="panel" aria-labelledby="entrevistas-title">
            <h2 id="entrevistas-title">Próximas entrevistas</h2>
            @forelse ($upcomingInterviews as $admission)
                <a class="list-row" href="{{ route('admin.admissions.show', $admission) }}">
                    <span>
                        <strong>{{ $admission->studentFullName() }}</strong>
                        <small>{{ $admission->grade }} · {{ $admission->guardian_name }}</small>
                    </span>
                    <time datetime="{{ $admission->interview_at->toIso8601String() }}">
                        {{ $admission->interview_at->translatedFormat('D j M') }}<br>{{ $admission->interview_at->shortTime() }}
                    </time>
                </a>
            @empty
                <p class="empty">No hay entrevistas agendadas.</p>
            @endforelse
        </section>

        <section class="panel" aria-labelledby="recientes-title">
            <div class="panel-head">
                <h2 id="recientes-title">Últimas solicitudes</h2>
                <a href="{{ route('admin.admissions.index') }}">Ver todas</a>
            </div>
            @forelse ($latest as $admission)
                <a class="list-row" href="{{ route('admin.admissions.show', $admission) }}">
                    <span>
                        <strong>{{ $admission->studentFullName() }}</strong>
                        <small>{{ $admission->code }} · {{ $admission->grade }} · {{ $admission->created_at->dayMonth() }}</small>
                    </span>
                    <x-status-badge :status="$admission->status" />
                </a>
            @empty
                <p class="empty">Todavía no llegan solicitudes desde la web.</p>
            @endforelse
        </section>
    </div>
</x-layouts.admin>
