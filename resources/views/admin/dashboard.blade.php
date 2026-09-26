@use('App\Enums\AdmissionStatus')
@php
    $cards = [
        [AdmissionStatus::Received, 'Por revisar'],
        [AdmissionStatus::InReview, 'En revisión'],
        [AdmissionStatus::InterviewScheduled, 'Con entrevista'],
        [AdmissionStatus::Accepted, 'Aceptadas'],
    ];
    $todo = collect($checklist)->reject(fn ($item) => $item['done']);
    $done = count($checklist) - $todo->count();
@endphp

<x-layouts.admin title="Panel">
    <div class="admin-head">
        <h1>Hola, {{ auth()->user()->name }}</h1>
        <p class="lead-sm">
            @php
                $todoLinks = array_filter([
                    $pending ? [route('admin.admissions.index', ['estado' => AdmissionStatus::Received->value]), trans_choice(':count solicitud nueva|:count solicitudes nuevas', $pending)] : null,
                    $newVolunteers ? [route('admin.volunteers.index', ['estado' => 'new']), trans_choice(':count voluntario nuevo|:count voluntarios nuevos', $newVolunteers)] : null,
                    $contactChanges ? [route('admin.people.contact-requests.index'), trans_choice(':count cambio de contacto|:count cambios de contacto', $contactChanges)] : null,
                ]);
            @endphp
            @if ($todoLinks)
                Tienes
                @foreach (array_values($todoLinks) as $i => [$url, $text])@if ($i > 0){{ $i === count($todoLinks) - 1 ? ' y' : ',' }}@endif <a href="{{ $url }}"><strong>{{ $text }}</strong></a>@endforeach
                por atender.
            @else
                No hay solicitudes ni voluntarios nuevos por atender.
            @endif
            @if ($mailQueue)
                <br>{{ trans_choice(':count correo a familias en cola|:count correos a familias en cola', $mailQueue) }}
                ({{ $mailToday }} enviados hoy; tope diario {{ config('school.family_mail.daily_limit') }}).
            @endif
            @if ($alertStudents)
                <br><a href="{{ route('admin.academic.alerts') }}">{{ trans_choice(':count estudiante con alertas|:count estudiantes con alertas', $alertStudents) }}</a> de rendimiento o inasistencia.
            @endif
        </p>
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

        <section class="panel" aria-labelledby="sitio-title">
            <div class="panel-head">
                <h2 id="sitio-title">Completa tu sitio</h2>
                <span class="muted">{{ $done }} de {{ count($checklist) }}</span>
            </div>
            <progress class="checklist-progress" max="{{ count($checklist) }}" value="{{ $done }}" aria-label="{{ $done }} de {{ count($checklist) }} listos"></progress>
            @if ($todo->isEmpty())
                <p class="empty">¡Todo listo! El sitio tiene todo su contenido.</p>
            @else
                <ul class="checklist">
                    @foreach ($todo as $item)
                        <li>
                            <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                            @if (! empty($item['hint']))
                                <small class="warn-text">{{ $item['hint'] }}</small>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="panel" aria-labelledby="eventos-title">
            <div class="panel-head">
                <h2 id="eventos-title">Próximos eventos</h2>
                <a href="{{ route('admin.events.index') }}">Ver calendario</a>
            </div>
            @forelse ($events as $event)
                <a class="list-row" href="{{ route('admin.events.edit', $event) }}">
                    <span>
                        <strong>{{ $event->title }}</strong>
                        <small>{{ $event->metaLabel() }}</small>
                    </span>
                    <time datetime="{{ $event->starts_at->toDateString() }}">{{ $event->starts_at->translatedFormat('D j M') }}</time>
                </a>
            @empty
                <p class="empty">No hay eventos próximos. <a href="{{ route('admin.events.create') }}">Agrega uno</a>.</p>
            @endforelse
        </section>
    </div>
</x-layouts.admin>
