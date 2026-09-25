<x-layouts.admin title="Acudientes">
    <div class="admin-head">
        <h1>Personas</h1>
    </div>

    @include('admin.people.partials.tabs')

    @if ($total === 0)
        <p class="empty panel">Todavía no hay acudientes. Se registran desde la ficha de cada estudiante o con la
            <a href="{{ route('admin.people.import.create') }}">importación desde Excel</a>.</p>
    @else
        @if ($withoutAccount > 0)
            <div class="notice bulk-accounts">
                <p>{{ trans_choice(':count acudiente no tiene cuenta del portal.|:count acudientes no tienen cuenta del portal.', $withoutAccount) }}
                    Puedes crearlas todas de una vez y obtener una hoja para imprimir con sus contraseñas temporales.</p>
                <form method="POST" action="{{ route('admin.people.accounts.bulk') }}"
                    data-confirm="¿Crear las cuentas pendientes? Las contraseñas temporales se muestran una sola vez, para imprimir.">
                    @csrf
                    <input type="hidden" name="type" value="guardians">
                    <button type="submit" class="btn btn-azul btn-sm">Crear cuentas pendientes</button>
                </form>
            </div>
        @endif

        <form class="filters" method="GET" action="{{ route('admin.people.guardians.index') }}" role="search">
            <label class="sr-only" for="q">Buscar</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Nombre o documento">
            <label class="sr-only" for="cuenta">Cuenta</label>
            <select id="cuenta" name="cuenta">
                <option value="">Todas las cuentas</option>
                <option value="sin-cuenta" @selected($account === 'sin-cuenta')>Sin cuenta</option>
                <option value="pendiente" @selected($account === 'pendiente')>No han cambiado la contraseña temporal</option>
            </select>
            <button type="submit" class="btn btn-line btn-sm">Filtrar</button>
            @if (request()->hasAny(['q', 'cuenta']))
                <a class="btn-link" href="{{ route('admin.people.guardians.index') }}">Quitar filtros</a>
            @endif
        </form>

        @if ($guardians->isEmpty())
            <p class="empty panel">Ningún acudiente coincide con la búsqueda.</p>
        @else
            <p class="lead-sm list-count">{{ trans_choice(':count acudiente|:count acudientes', $guardians->total()) }}</p>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Acudiente</th>
                            <th scope="col">Documento</th>
                            <th scope="col">Teléfono</th>
                            <th scope="col">Acudidos</th>
                            <th scope="col">Cuenta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($guardians as $guardian)
                            <tr>
                                <td data-label="Acudiente"><a class="strong" href="{{ route('admin.people.guardians.edit', $guardian) }}">{{ $guardian->sortName() }}</a></td>
                                <td data-label="Documento" class="nowrap">{{ $guardian->documentLabel() }}</td>
                                <td data-label="Teléfono" class="nowrap">{{ $guardian->phone ?? '—' }}</td>
                                <td data-label="Acudidos">
                                    @forelse ($guardian->students as $student)
                                        {{ $student->fullName() }} <small>({{ $student->pivot->relationship->label() }})</small>@if (! $loop->last)<br>@endif
                                    @empty
                                        <span class="badge badge-in_review">Sin acudidos</span>
                                    @endforelse
                                </td>
                                <td data-label="Cuenta">
                                    @if (! $guardian->user)
                                        <span class="muted">Sin cuenta</span>
                                    @elseif (! $guardian->user->is_active)
                                        Desactivada
                                    @else
                                        {{ $guardian->user->must_change_password ? 'Temporal' : 'Activa' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $guardians->links('partials.pagination') }}
        @endif
    @endif
</x-layouts.admin>
