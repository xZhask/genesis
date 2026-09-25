<x-layouts.admin title="Asignaciones docentes">
    <div class="admin-head row">
        <h1>Académico</h1>
        @if ($years->count() > 1)
            <form method="GET" action="{{ route('admin.academic.assignments.index') }}" class="inline-select">
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

    @if (! $section)
        <p class="empty panel">Primero crea las <a href="{{ route('admin.academic.sections.index') }}">secciones</a> del año lectivo.</p>
    @else
        <nav class="grade-switch" aria-label="Sección">
            @foreach ($sections as $option)
                <a href="{{ route('admin.academic.assignments.index', ['lectivo' => $year->year, 'seccion' => $option->id]) }}"
                    @if ($option->is($section)) aria-current="page" @endif>{{ $option->label() }}</a>
            @endforeach
        </nav>

        @if ($teachers->isEmpty())
            <p class="notice">Todavía no hay cuentas de docentes. Créalas en <a href="{{ route('admin.people.staff.create') }}">Personas → Docentes y administración</a>.</p>
        @endif

        <section class="panel" aria-labelledby="asig-title">
            <div class="panel-head">
                <h2 id="asig-title">{{ $section->label() }} · {{ $year->year }}</h2>
                <span class="muted">{{ $assigned->count() }} de {{ $subjects->count() }} materias con docente</span>
            </div>

            @if ($subjects->isEmpty())
                <p class="hint">{{ $section->grade->name }} no tiene materias en su <a href="{{ route('admin.academic.curriculum.edit', $section->grade) }}">plan de estudios</a>.</p>
            @else
                <form class="form compact" method="POST" action="{{ route('admin.academic.assignments.update', $section) }}" novalidate data-form>
                    @csrf
                    @method('PUT')
                    <x-field name="homeroom_teacher_id" label="Director de grupo" optional>
                        <select id="homeroom_teacher_id" name="homeroom_teacher_id">
                            <option value="">Sin asignar</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected((string) old('homeroom_teacher_id', $section->homeroom_teacher_id) === (string) $teacher->id)>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </x-field>

                    <div class="table-wrap">
                        <table class="table assignment-table">
                            <thead>
                                <tr>
                                    <th scope="col">Materia</th>
                                    <th scope="col">Docente</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($subjects as $subject)
                                    <tr>
                                        <th scope="row">{{ $subject->name }} <small>{{ $subject->area->name }}</small></th>
                                        <td data-label="Docente">
                                            <label class="sr-only" for="t-{{ $subject->id }}">Docente de {{ $subject->name }}</label>
                                            <select id="t-{{ $subject->id }}" name="teachers[{{ $subject->id }}]" @error("teachers.{$subject->id}") aria-invalid="true" @enderror>
                                                <option value="">Sin docente</option>
                                                @foreach ($teachers as $teacher)
                                                    <option value="{{ $teacher->id }}" @selected((string) old("teachers.{$subject->id}", $assigned[$subject->id] ?? '') === (string) $teacher->id)>{{ $teacher->name }}</option>
                                                @endforeach
                                            </select>
                                            @error("teachers.{$subject->id}")
                                                <p class="error">{{ $message }}</p>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-azul" data-submit data-loading-text="Guardando…"><span>Guardar asignaciones</span></button>
                </form>
            @endif
        </section>
    @endif
</x-layouts.admin>
