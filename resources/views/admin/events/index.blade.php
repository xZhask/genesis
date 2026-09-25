<x-layouts.admin title="Eventos">
    <div class="admin-head row">
        <h1>Eventos del calendario</h1>
        <a class="btn btn-sol" href="{{ route('admin.events.create') }}">+ Nuevo evento</a>
    </div>

    <nav class="tabs" aria-label="Periodo">
        <a href="{{ route('admin.events.index') }}" @if (! $past) aria-current="page" @endif>Próximos</a>
        <a href="{{ route('admin.events.index', ['vista' => 'pasados']) }}" @if ($past) aria-current="page" @endif>Pasados</a>
    </nav>

    @if ($events->isEmpty())
        <p class="empty panel">{{ $past ? 'No hay eventos pasados.' : 'No hay eventos próximos.' }} <a href="{{ route('admin.events.create') }}">Agrega uno</a>.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Evento</th>
                        <th scope="col">Horario y lugar</th>
                        <th scope="col">Nivel</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($events as $event)
                        <tr>
                            <td data-label="Fecha" class="nowrap">{{ $event->starts_at->translatedFormat('D j \d\e M') }}</td>
                            <td data-label="Evento"><a href="{{ route('admin.events.edit', $event) }}">{{ $event->title }}</a></td>
                            <td data-label="Horario y lugar">{{ $event->metaLabel() }}</td>
                            <td data-label="Nivel">{{ $event->levelName() ?? 'Todo el colegio' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $events->links('partials.pagination') }}
    @endif
</x-layouts.admin>
