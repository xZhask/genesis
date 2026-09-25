<x-layouts.admin title="Áreas y materias">
    <div class="admin-head">
        <h1>Académico</h1>
    </div>

    @include('admin.academic.partials.tabs')

    <p class="lead-sm">La nota de cada área es el promedio de sus materias (como en el boletín actual). Áreas y materias provisionales hasta que el colegio las confirme.</p>

    <div class="admin-grid detail">
        <div class="stack">
            @foreach ($areas as $area)
                <section class="panel" aria-labelledby="area-{{ $area->id }}">
                    <div class="panel-head">
                        <h2 id="area-{{ $area->id }}">{{ $area->name }}</h2>
                        @if ($area->subjects->isEmpty())
                            <form method="POST" action="{{ route('admin.academic.areas.destroy', $area) }}" data-confirm="¿Eliminar el área {{ $area->name }}?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Eliminar área</button>
                            </form>
                        @endif
                    </div>
                    @forelse ($area->subjects as $subject)
                        <form method="POST" action="{{ route('admin.academic.subjects.update', $subject) }}" class="subject-row">
                            @csrf
                            @method('PUT')
                            <label class="sr-only" for="subject-{{ $subject->id }}">Nombre de la materia</label>
                            <input id="subject-{{ $subject->id }}" name="name" type="text" maxlength="80" value="{{ $subject->name }}">
                            <label class="sr-only" for="subject-area-{{ $subject->id }}">Área de {{ $subject->name }}</label>
                            <select id="subject-area-{{ $subject->id }}" name="area_id">
                                @foreach ($areas as $option)
                                    <option value="{{ $option->id }}" @selected($option->is($area))>{{ $option->name }}</option>
                                @endforeach
                            </select>
                            <small class="muted">{{ trans_choice('{0} ningún grado|{1} :count grado|[2,*] :count grados', $subject->grades_count) }}</small>
                            <button type="submit" class="btn btn-line btn-sm">Guardar</button>
                        </form>
                        @if ($subject->grades_count === 0)
                            <form method="POST" action="{{ route('admin.academic.subjects.destroy', $subject) }}" class="subject-delete"
                                data-confirm="¿Eliminar la materia {{ $subject->name }}?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="link-danger">Eliminar {{ $subject->name }}</button>
                            </form>
                        @endif
                    @empty
                        <p class="empty">Sin materias.</p>
                    @endforelse
                </section>
            @endforeach
        </div>

        <div class="stack">
            <section class="panel" aria-labelledby="nueva-materia">
                <h2 id="nueva-materia">Nueva materia</h2>
                <form class="form compact" method="POST" action="{{ route('admin.academic.subjects.store') }}" novalidate data-form>
                    @csrf
                    <x-field name="name" label="Nombre">
                        <input id="name" name="name" type="text" maxlength="80" value="{{ old('name') }}">
                    </x-field>
                    <x-field name="area_id" label="Área">
                        <select id="area_id" name="area_id">
                            <option value="">Elige el área</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" @selected(old('area_id') == $area->id)>{{ $area->name }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <button type="submit" class="btn btn-azul" data-submit><span>Crear materia</span></button>
                </form>
            </section>

            <section class="panel" aria-labelledby="nueva-area">
                <h2 id="nueva-area">Nueva área</h2>
                <form class="form compact" method="POST" action="{{ route('admin.academic.areas.store') }}" novalidate data-form>
                    @csrf
                    <x-field name="area_name" label="Nombre">
                        <input id="area_name" name="area_name" type="text" maxlength="80" value="{{ old('area_name') }}">
                    </x-field>
                    <button type="submit" class="btn btn-line" data-submit><span>Crear área</span></button>
                </form>
            </section>
        </div>
    </div>
</x-layouts.admin>
