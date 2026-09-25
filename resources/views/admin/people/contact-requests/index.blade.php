<x-layouts.admin title="Cambios de contacto">
    <div class="admin-head">
        <h1>Personas</h1>
        <p class="lead-sm">Los acudientes piden desde el portal cambiar su teléfono o su correo. El dato cambia solo cuando lo apruebas.</p>
    </div>

    @include('admin.people.partials.tabs')

    @error('request')
        <div class="error-summary" role="alert"><p>{{ $message }}</p></div>
    @enderror

    <section aria-labelledby="pendientes-title">
        <h2 id="pendientes-title" class="section-title">Por revisar ({{ $pending->count() }})</h2>

        @forelse ($pending as $item)
            <article class="panel contact-request">
                <header class="contact-request-head">
                    <div>
                        <a class="strong" href="{{ route('admin.people.guardians.edit', $item->guardian) }}">{{ $item->guardian->fullName() }}</a>
                        <small>{{ $item->guardian->documentLabel() }}
                            @if ($item->guardian->students->isNotEmpty())
                                · Acudiente de {{ $item->guardian->students->map->fullName()->join(', ', ' y ') }}
                            @endif
                        </small>
                    </div>
                    <small class="nowrap">Pedido el {{ $item->created_at->longDate() }}</small>
                </header>

                <dl class="contact-change">
                    <div><dt>{{ $item->field->label() }} registrado</dt><dd>{{ $item->old_value ?: 'Sin registrar' }}</dd></div>
                    <div><dt>{{ $item->field->label() }} nuevo</dt><dd><strong>{{ $item->new_value }}</strong></dd></div>
                </dl>

                @if ($item->isStale())
                    <p class="notice notice-warn">El dato actual ({{ $item->guardian->{$item->field->value} ?: 'sin registrar' }}) ya no es el que tenía cuando se pidió el cambio. Revísalo antes de aprobar.</p>
                @endif
                @if ($item->field === App\Enums\ContactField::Email && $item->guardian->user?->role === App\Enums\Role::Guardian)
                    <p class="muted">Al aprobarlo, también cambia el correo de su cuenta del portal (con el que recupera la contraseña).</p>
                @endif

                <div class="contact-actions">
                    <form method="POST" action="{{ route('admin.people.contact-requests.approve', $item) }}">
                        @csrf
                        <button type="submit" class="btn btn-azul btn-sm">Aprobar</button>
                    </form>
                    <form class="form contact-reject" method="POST" action="{{ route('admin.people.contact-requests.reject', $item) }}">
                        @csrf
                        <label class="sr-only" for="note-{{ $item->id }}">Motivo del rechazo (opcional)</label>
                        <input id="note-{{ $item->id }}" name="note" type="text" maxlength="300" placeholder="Motivo (opcional)" title="El acudiente verá este motivo">
                        <button type="submit" class="btn btn-line btn-sm">Rechazar</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="empty panel">No hay cambios de contacto por revisar.</p>
        @endforelse
    </section>

    @if ($history->isNotEmpty())
        <section aria-labelledby="historial-title">
            <h2 id="historial-title" class="section-title">Revisadas</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Acudiente</th>
                            <th scope="col">Dato</th>
                            <th scope="col">Cambio pedido</th>
                            <th scope="col">Decisión</th>
                            <th scope="col">Revisó</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($history as $item)
                            <tr>
                                <td data-label="Acudiente"><a href="{{ route('admin.people.guardians.edit', $item->guardian) }}">{{ $item->guardian->fullName() }}</a></td>
                                <td data-label="Dato">{{ $item->field->label() }}</td>
                                <td data-label="Cambio pedido">{{ $item->old_value ?: 'Sin registrar' }} → {{ $item->new_value }}</td>
                                <td data-label="Decisión">
                                    <span class="badge badge-{{ $item->status->badge() }}">{{ $item->status->label() }}</span>
                                    @if ($item->note)<small class="block">{{ $item->note }}</small>@endif
                                </td>
                                <td data-label="Revisó" class="nowrap">{{ $item->reviewer?->name ?? '—' }}<small class="block">{{ $item->reviewed_at->longDate() }}</small></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $history->links('partials.pagination') }}
        </section>
    @endif
</x-layouts.admin>
