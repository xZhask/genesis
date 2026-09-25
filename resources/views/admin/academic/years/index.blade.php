<x-layouts.admin title="Años lectivos">
    <div class="admin-head">
        <h1>Académico</h1>
        <p class="lead-sm">Estructura del portal: años lectivos y periodos, secciones, materias y plan de estudios.</p>
    </div>

    @include('admin.academic.partials.tabs')

    <div class="admin-grid detail">
        <section class="panel" aria-labelledby="anos-title">
            <h2 id="anos-title">Años lectivos</h2>
            @forelse ($years as $year)
                <a class="list-row" href="{{ route('admin.academic.years.edit', $year) }}">
                    <span>
                        <strong>{{ $year->year }}</strong>
                        @if ($year->is_current)
                            <span class="badge badge-accepted">Actual</span>
                        @endif
                        <small>
                            {{ $year->starts_on->longDate() }} – {{ $year->ends_on->longDate() }}
                            · {{ trans_choice(':count sección|:count secciones', $year->sections_count) }}
                            · {{ $year->periods->filter->isClosed()->count() }} de {{ $year->periods->count() }} periodos cerrados
                        </small>
                    </span>
                    <span aria-hidden="true">→</span>
                </a>
            @empty
                <p class="empty">Todavía no hay años lectivos. Crea el primero para empezar a usar el portal.</p>
            @endforelse
        </section>

        <section class="panel" aria-labelledby="nuevo-title">
            <h2 id="nuevo-title">Nuevo año lectivo</h2>
            <form class="form compact" method="POST" action="{{ route('admin.academic.years.store') }}" novalidate data-form>
                @csrf
                <x-field name="year" label="Año">
                    <input id="year" name="year" type="number" min="2026" max="2100" inputmode="numeric" value="{{ old('year', $suggestedYear) }}">
                </x-field>
                <div class="field-group even">
                    <x-field name="starts_on" label="Inicio de clases">
                        <input id="starts_on" name="starts_on" type="date" value="{{ old('starts_on') }}">
                    </x-field>
                    <x-field name="ends_on" label="Fin de clases">
                        <input id="ends_on" name="ends_on" type="date" value="{{ old('ends_on') }}">
                    </x-field>
                </div>
                <label class="check">
                    <input type="checkbox" name="is_current" value="1" @checked(old('is_current', $years->isEmpty()))>
                    <span>Es el año lectivo actual del portal</span>
                </label>
                <p class="hint">Se crean {{ config('school.academic.periods') }} periodos de igual duración y peso; después puedes ajustar sus fechas.</p>
                <button type="submit" class="btn btn-azul" data-submit data-loading-text="Creando…"><span>Crear año lectivo</span></button>
            </form>
        </section>
    </div>
</x-layouts.admin>
