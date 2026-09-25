<x-layouts.admin title="Docentes y administración">
    <div class="admin-head row">
        <h1>Personas</h1>
        <a class="btn btn-azul btn-sm" href="{{ route('admin.people.staff.create') }}">Nueva cuenta</a>
    </div>

    @include('admin.people.partials.tabs')

    @foreach (['teacher' => 'Docentes', 'admin' => 'Administración'] as $role => $heading)
        <section class="panel staff-panel" aria-labelledby="staff-{{ $role }}">
            <h2 id="staff-{{ $role }}">{{ $heading }}</h2>
            @forelse ($staff[$role] ?? [] as $user)
                <a class="list-row" href="{{ route('admin.people.staff.edit', $user) }}">
                    <span>
                        <strong>{{ $user->name }}</strong>
                        @if (! $user->is_active)
                            <span class="badge badge-withdrawn">Desactivada</span>
                        @elseif ($user->must_change_password)
                            <span class="badge badge-in_review">Contraseña temporal</span>
                        @endif
                        <small>
                            {{ $user->document_number ?? 'Sin documento' }}@if ($user->email) · {{ $user->email }}@endif
                            @if ($user->guardian) · También es acudiente @endif
                        </small>
                    </span>
                    <span aria-hidden="true">→</span>
                </a>
            @empty
                <p class="hint">{{ $role === 'teacher' ? 'Todavía no hay cuentas de docentes. Créalas para asignarles sus materias y secciones.' : 'No hay más cuentas de administración.' }}</p>
            @endforelse
        </section>
    @endforeach
</x-layouts.admin>
