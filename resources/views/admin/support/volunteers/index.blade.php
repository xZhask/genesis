@use('App\Enums\VolunteerStatus')

<x-layouts.admin title="Voluntarios">
    <div class="admin-head">
        <h1>Apóyanos</h1>
    </div>

    @include('admin.support.partials.tabs')

    <nav class="filters-inline" aria-label="Filtrar por estado">
        <a href="{{ route('admin.volunteers.index') }}" @if (! $status) aria-current="page" @endif>Todas ({{ $counts->sum() }})</a>
        @foreach (VolunteerStatus::cases() as $case)
            <a href="{{ route('admin.volunteers.index', ['estado' => $case->value]) }}" @if ($status === $case) aria-current="page" @endif>
                {{ $case === VolunteerStatus::New ? 'Nuevas' : ($case === VolunteerStatus::Contacted ? 'Contactadas' : 'Archivadas') }} ({{ $counts[$case->value] ?? 0 }})
            </a>
        @endforeach
    </nav>

    @if ($applications->isEmpty())
        <p class="empty panel">
            {{ $status ? 'No hay solicitudes en este estado.' : 'Todavía nadie se ha ofrecido como voluntario. Las solicitudes llegan desde el formulario de /apoyanos.' }}
        </p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Quiere ayudar en</th>
                        <th scope="col">Disponibilidad</th>
                        <th scope="col">Recibida</th>
                        <th scope="col">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($applications as $application)
                        <tr>
                            <td data-label="Nombre"><a class="strong" href="{{ route('admin.volunteers.show', $application) }}">{{ $application->name }}</a></td>
                            <td data-label="Quiere ayudar en">{{ $application->areasLabel() }}</td>
                            <td data-label="Disponibilidad">{{ $application->availability->label() }}</td>
                            <td data-label="Recibida" class="nowrap">{{ $application->created_at->longDate() }}</td>
                            <td data-label="Estado"><span class="badge badge-{{ $application->status->badge() }}">{{ $application->status->label() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $applications->links('partials.pagination') }}
    @endif
</x-layouts.admin>
