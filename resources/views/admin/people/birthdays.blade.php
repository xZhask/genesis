<x-layouts.admin title="Cumpleaños">
    <div class="admin-head">
        <h1>Personas</h1>
    </div>

    @include('admin.people.partials.tabs')

    <form class="form compact birthday-filter" method="GET" action="{{ route('admin.people.birthdays') }}">
        <div class="field">
            <label for="tipo">Mostrar</label>
            <select id="tipo" name="tipo">
                <option value="">Estudiantes y personal</option>
                <option value="student" @selected($kind === 'student')>Solo estudiantes</option>
                <option value="staff" @selected($kind === 'staff')>Solo docentes y administración</option>
            </select>
        </div>
        <div class="field">
            <label for="mes">Mes</label>
            <select id="mes" name="mes">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" @selected($month === $m)>{{ ucfirst(Illuminate\Support\Carbon::create(2000, $m, 1)->translatedFormat('F')) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-line btn-sm">Ver</button>
    </form>

    @if ($missing['students'] || $missing['staff'])
        <p class="panel muted-panel birthday-missing">
            Sin fecha de nacimiento no aparecen aquí ni reciben tarjeta:
            @if ($missing['students'])
                <a href="{{ route('admin.people.students.index') }}">{{ trans_choice(':count estudiante|:count estudiantes', $missing['students']) }}</a>
            @endif
            @if ($missing['students'] && $missing['staff'])
                y
            @endif
            @if ($missing['staff'])
                <a href="{{ route('admin.people.staff.index') }}">{{ trans_choice(':count persona del personal|:count personas del personal', $missing['staff']) }}</a>
            @endif
        </p>
    @endif

    <div class="stack">
    <section class="panel staff-panel" aria-labelledby="hoy-title">
        <h2 id="hoy-title">Hoy</h2>
        @forelse ($today as $row)
            @include('admin.people.partials.birthday-row', ['card' => true])
        @empty
            <p class="hint">Hoy no hay cumpleaños.</p>
        @endforelse
    </section>

    <section class="panel staff-panel" aria-labelledby="pronto-title">
        <h2 id="pronto-title">Próximos {{ $window - 1 }} días</h2>
        @forelse ($upcoming as $row)
            @include('admin.people.partials.birthday-row', ['card' => true])
        @empty
            <p class="hint">No hay cumpleaños en los próximos días.</p>
        @endforelse
    </section>

    <section class="panel staff-panel" aria-labelledby="mes-title">
        <h2 id="mes-title">{{ ucfirst(Illuminate\Support\Carbon::create(2000, $month, 1)->translatedFormat('F')) }}</h2>
        @forelse ($byMonth as $row)
            @include('admin.people.partials.birthday-row', ['card' => false])
        @empty
            <p class="hint">No hay cumpleaños registrados en este mes.</p>
        @endforelse
    </section>
    </div>
</x-layouts.admin>
