@use('App\Enums\FeedbackStatus')
@use('App\Enums\FeedbackType')
@php
    $tabs = ['abiertos' => 'Por responder', 'respondidos' => 'Respondidos', 'todos' => 'Todos'];
    $query = fn (array $extra = []) => array_filter([
        'tipo' => $filters['tipo'] ?? null,
        'docente' => $filters['docente'] ?? null,
        'estado' => $status === 'abiertos' ? null : $status,
        ...$extra,
    ]);
@endphp

<x-layouts.admin title="Buzón de sugerencias">
    <div class="admin-head">
        <h1>Buzón de sugerencias</h1>
        <p class="lead-sm">Lo que escriben estudiantes y acudientes desde el portal. Solo lo ven las cuentas autorizadas; el docente mencionado no lo ve. Si compartes algo con él, hazlo sin dar el nombre de quien escribió.</p>
    </div>

    <nav class="tabs" aria-label="Filtrar por estado">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.feedback.index', $query(['estado' => $key === 'abiertos' ? null : $key])) }}" @if ($status === $key) aria-current="page" @endif>
                {{ $label }}@if ($key === 'abiertos') <span>{{ $openCount }}</span>@endif
            </a>
        @endforeach
    </nav>

    <form class="filters" method="GET" action="{{ route('admin.feedback.index') }}">
        @if ($status !== 'abiertos')
            <input type="hidden" name="estado" value="{{ $status }}">
        @endif
        <label class="sr-only" for="tipo">Tipo</label>
        <select id="tipo" name="tipo">
            <option value="">Todos los tipos</option>
            @foreach (FeedbackType::cases() as $type)
                <option value="{{ $type->value }}" @selected(($filters['tipo'] ?? null) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        @if ($teachers->isNotEmpty())
            <label class="sr-only" for="docente">Docente mencionado</label>
            <select id="docente" name="docente">
                <option value="">Todos los docentes</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected((int) ($filters['docente'] ?? 0) === $teacher->id)>{{ $teacher->name }}</option>
                @endforeach
            </select>
        @endif
        <button type="submit" class="btn btn-azul btn-sm">Filtrar</button>
        @if (($filters['tipo'] ?? null) || ($filters['docente'] ?? null))
            <a class="btn-link" href="{{ route('admin.feedback.index', array_filter(['estado' => $status === 'abiertos' ? null : $status])) }}">Limpiar</a>
        @endif
    </form>

    @forelse ($messages as $message)
        @php
            $bag = 'reply'.$message->id;
        @endphp
        <article class="panel feedback-item" id="mensaje-{{ $message->id }}" aria-labelledby="mensaje-{{ $message->id }}-title">
            <header class="contact-request-head">
                <div>
                    <h2 id="mensaje-{{ $message->id }}-title" class="feedback-title">
                        <span class="badge badge-type">{{ $message->type->label() }}</span>
                        {{ $message->aboutLabel() }}
                    </h2>
                    <small>{{ $message->author?->name ?? 'Cuenta eliminada' }} · {{ $message->authorLabel() }}</small>
                </div>
                <div class="feedback-meta">
                    <span class="badge badge-{{ $message->status->badge() }}">{{ $message->status->label() }}</span>
                    <small class="block">{{ $message->created_at->longDate() }}, {{ $message->created_at->shortTime() }}</small>
                </div>
            </header>

            <p class="feedback-body">{{ $message->body }}</p>

            @if ($message->isAnswered())
                <div class="feedback-reply">
                    <p class="feedback-reply-head">Respuesta de {{ $message->replier?->name ?? 'el colegio' }} · {{ $message->replied_at->longDate() }}
                        @if ($message->reply_seen_at) · <span class="muted">vista</span>@endif
                    </p>
                    <p>{{ $message->reply }}</p>
                </div>
            @endif

            <details class="feedback-answer" @if ($errors->getBag($bag)->any() || ! $message->isAnswered()) open @endif>
                <summary>{{ $message->isAnswered() ? 'Corregir la respuesta' : 'Responder' }}</summary>
                <form class="form compact" method="POST" action="{{ route('admin.feedback.answer', $message) }}" novalidate data-form>
                    @csrf
                    <div class="field @if ($errors->getBag($bag)->has('reply')) has-error @endif">
                        <label class="label" for="reply-{{ $message->id }}">Respuesta</label>
                        <p class="hint" id="reply-{{ $message->id }}-hint">La verá {{ $message->author_role === App\Enums\Role::Student ? 'el estudiante' : 'el acudiente' }} en su buzón del portal. Si el caso necesita una conversación, invítalo a acercarse al colegio.</p>
                        <textarea id="reply-{{ $message->id }}" name="reply" rows="3" maxlength="1500" aria-describedby="reply-{{ $message->id }}-hint">{{ $errors->getBag($bag)->any() ? old('reply') : $message->reply }}</textarea>
                        @if ($errors->getBag($bag)->has('reply'))
                            <p class="error">{{ $errors->getBag($bag)->first('reply') }}</p>
                        @endif
                    </div>
                    <div class="contact-actions">
                        <button type="submit" class="btn btn-azul btn-sm" data-submit data-loading-text="Enviando…"><span>{{ $message->isAnswered() ? 'Guardar respuesta' : 'Enviar respuesta' }}</span></button>
                    </div>
                </form>
                @if ($message->status === FeedbackStatus::Received)
                    <form method="POST" action="{{ route('admin.feedback.review', $message) }}" class="feedback-review">
                        @csrf
                        <button type="submit" class="btn btn-line btn-sm">Marcar en revisión</button>
                        <small class="muted">Úsalo si necesitas tiempo para averiguar antes de responder.</small>
                    </form>
                @endif
            </details>
        </article>
    @empty
        <p class="empty panel">{{ $status === 'abiertos' ? 'No hay mensajes por responder.' : 'No hay mensajes con estos filtros.' }}</p>
    @endforelse

    {{ $messages->links('partials.pagination') }}
</x-layouts.admin>
