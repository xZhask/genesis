<x-layouts.admin title="Nuevo estudiante">
    <p class="back"><a href="{{ route('admin.people.students.index') }}">← Estudiantes</a></p>
    <div class="admin-head">
        <h1>Nuevo estudiante</h1>
        <p class="lead-sm">Después de guardarlo podrás vincular a sus acudientes.</p>
    </div>

    <form class="form admin-form narrow-form" method="POST" action="{{ route('admin.people.students.store') }}" novalidate data-form>
        @csrf
        <section class="panel">
            <h2>Datos del estudiante</h2>
            @include('admin.people.students.partials.fields')
        </section>

        <section class="panel">
            <h2>Matrícula {{ $year?->year }}</h2>
            @if ($year && $sections->isNotEmpty())
                <x-field name="section_id" label="Sección" optional>
                    @include('admin.people.partials.section-select', ['name' => 'section_id', 'selected' => old('section_id'), 'placeholder' => 'Sin matricular por ahora'])
                </x-field>
            @else
                <p class="hint">Para matricular, primero crea el año lectivo actual y sus secciones en <a href="{{ route('admin.academic.sections.index') }}">Académico</a>.</p>
            @endif
        </section>

        <button type="submit" class="btn btn-azul" data-submit data-loading-text="Guardando…"><span>Guardar estudiante</span></button>
    </form>
</x-layouts.admin>
