@use('App\Enums\AdmissionStatus')
@php
    $filters = fn (array $extra = []) => array_filter([
        'q' => $search,
        'anio' => $year,
        ...$extra,
    ]);
@endphp

<x-layouts.admin title="Solicitudes">
    <div class="admin-head">
        <h1>Solicitudes de pre-inscripción</h1>
    </div>

    <form class="filters" method="GET" action="{{ route('admin.admissions.index') }}" role="search">
        @if ($status)
            <input type="hidden" name="estado" value="{{ $status->value }}">
        @endif
        <label class="sr-only" for="q">Buscar</label>
        <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Nombre, código o celular">
        <label class="sr-only" for="anio">Año lectivo</label>
        <select id="anio" name="anio">
            <option value="">Todos los años</option>
            @foreach ($years as $y)
                <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-azul btn-sm">Buscar</button>
        @if ($search || $year)
            <a class="btn-link" href="{{ route('admin.admissions.index', array_filter(['estado' => $status?->value])) }}">Limpiar</a>
        @endif
    </form>

    <nav class="tabs" aria-label="Filtrar por estado">
        <a href="{{ route('admin.admissions.index', $filters()) }}" @if (! $status) aria-current="page" @endif>
            Todas <span>{{ $counts->sum() }}</span>
        </a>
        @foreach (AdmissionStatus::cases() as $case)
            <a href="{{ route('admin.admissions.index', $filters(['estado' => $case->value])) }}" @if ($status === $case) aria-current="page" @endif>
                {{ $case->label() }} <span>{{ $counts[$case->value] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    @if ($admissions->isEmpty())
        <p class="empty panel">No hay solicitudes con estos filtros.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Estudiante</th>
                        <th scope="col">Grado</th>
                        <th scope="col">Acudiente</th>
                        <th scope="col">Celular</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Recibida</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($admissions as $admission)
                        <tr>
                            <td data-label="Código"><a href="{{ route('admin.admissions.show', $admission) }}">{{ $admission->code }}</a></td>
                            <td data-label="Estudiante" class="strong">{{ $admission->studentFullName() }}</td>
                            <td data-label="Grado">{{ $admission->grade }} <small>{{ $admission->school_year }}</small></td>
                            <td data-label="Acudiente">{{ $admission->guardian_name }}</td>
                            <td data-label="Celular" class="nowrap">{{ $admission->formattedPhone() }}</td>
                            <td data-label="Estado"><x-status-badge :status="$admission->status" /></td>
                            <td data-label="Recibida" class="nowrap">{{ $admission->created_at->dayMonth() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $admissions->links('admin.partials.pagination') }}
    @endif
</x-layouts.admin>
