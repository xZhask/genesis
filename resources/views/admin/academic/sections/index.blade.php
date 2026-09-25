<x-layouts.admin title="Grados y secciones">
    <div class="admin-head row">
        <h1>Académico</h1>
        @if ($years->count() > 1)
            <form method="GET" action="{{ route('admin.academic.sections.index') }}" class="inline-select">
                <label for="lectivo">Año lectivo</label>
                <select id="lectivo" name="lectivo" data-autosubmit>
                    @foreach ($years as $option)
                        <option value="{{ $option->year }}" @selected($option->is($year))>{{ $option->year }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn btn-line btn-sm">Ver</button></noscript>
            </form>
        @endif
    </div>

    @include('admin.academic.partials.tabs')

    @if (! $year)
        <p class="empty panel">Primero <a href="{{ route('admin.academic.years.index') }}">crea un año lectivo</a>; las secciones pertenecen a un año.</p>
    @else
        <div class="panel-head section-intro">
            <p class="lead-sm">Secciones de {{ $year->year }}. Un grado puede tener varias (por ejemplo, 1 y 2, o A y B).</p>
            @if ($grades->contains(fn ($g) => $g->sections->isEmpty()))
                <form method="POST" action="{{ route('admin.academic.sections.defaults', $year) }}">
                    @csrf
                    <button type="submit" class="btn btn-line btn-sm">Crear la sección 1 en los grados que no tienen</button>
                </form>
            @endif
        </div>

        @foreach ($grades->groupBy('level') as $levelKey => $levelGrades)
            <section class="panel level-panel lvl-{{ $levelKey }}" aria-labelledby="nivel-{{ $levelKey }}">
                <h2 id="nivel-{{ $levelKey }}">{{ $levels[$levelKey]['name'] }}</h2>
                <ul class="grade-rows">
                    @foreach ($levelGrades as $grade)
                        <li>
                            <strong class="grade-name">{{ $grade->name }}</strong>
                            <div class="section-chips">
                                @forelse ($grade->sections as $section)
                                    <form method="POST" action="{{ route('admin.academic.sections.destroy', $section) }}" class="section-chip"
                                        data-confirm="¿Eliminar la sección {{ $section->label() }}?">
                                        @csrf
                                        @method('DELETE')
                                        <span>{{ $section->label() }}</span>
                                        <button type="submit" aria-label="Eliminar la sección {{ $section->label() }}">×</button>
                                    </form>
                                @empty
                                    <span class="muted">Sin secciones</span>
                                @endforelse
                            </div>
                            <form method="POST" action="{{ route('admin.academic.sections.store', $year) }}" class="add-section">
                                @csrf
                                <input type="hidden" name="grade_id" value="{{ $grade->id }}">
                                <label class="sr-only" for="section-{{ $grade->id }}">Nueva sección de {{ $grade->name }}</label>
                                <input id="section-{{ $grade->id }}" name="name" type="text" maxlength="10" placeholder="Nueva"
                                    value="{{ old('grade_id') == $grade->id ? old('name') : '' }}">
                                <button type="submit" class="btn btn-line btn-sm">Agregar</button>
                            </form>
                            @if ($errors->has('name') && old('grade_id') == $grade->id)
                                <p class="error">{{ $errors->first('name') }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    @endif
</x-layouts.admin>
