<x-layouts.admin title="Estudiantes">
    <div class="admin-head row">
        <h1>Personas</h1>
        <a class="btn btn-azul btn-sm" href="{{ route('admin.people.students.create') }}">Nuevo estudiante</a>
    </div>

    @include('admin.people.partials.tabs')

    @if ($total === 0)
        <div class="empty panel">
            <p>Todavía no hay estudiantes. Puedes <a href="{{ route('admin.people.import.create') }}">importarlos desde Excel</a>
                con sus acudientes, o <a href="{{ route('admin.people.students.create') }}">registrarlos uno por uno</a>.</p>
        </div>
    @else
        @if ($awaitingAccount > 0)
            <div class="notice bulk-accounts">
                <p>{{ trans_choice(':count estudiante de '.config('school.academic.student_accounts_from').' en adelante no tiene cuenta del portal.|:count estudiantes de '.config('school.academic.student_accounts_from').' en adelante no tienen cuenta del portal.', $awaitingAccount) }}</p>
                <form method="POST" action="{{ route('admin.people.accounts.bulk') }}"
                    data-confirm="¿Crear las cuentas pendientes? Las contraseñas temporales se muestran una sola vez, para imprimir.">
                    @csrf
                    <input type="hidden" name="type" value="students">
                    <button type="submit" class="btn btn-azul btn-sm">Crear cuentas pendientes</button>
                </form>
            </div>
        @endif

        <form class="filters" method="GET" action="{{ route('admin.people.students.index') }}" role="search">
            <label class="sr-only" for="q">Buscar</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Nombre o documento">
            @if ($years->count() > 1)
                <label class="sr-only" for="lectivo">Año lectivo</label>
                <select id="lectivo" name="lectivo">
                    @foreach ($years as $option)
                        <option value="{{ $option->year }}" @selected($option->is($year))>{{ $option->year }}</option>
                    @endforeach
                </select>
            @endif
            @if ($year)
                <label class="sr-only" for="seccion">Sección</label>
                <select id="seccion" name="seccion">
                    <option value="">Todas las secciones</option>
                    @foreach ($sections as $level => $levelSections)
                        <optgroup label="{{ $level }}">
                            @foreach ($levelSections as $option)
                                <option value="{{ $option->id }}" @selected((string) $section === (string) $option->id)>{{ $option->label() }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                    <option value="sin-matricula" @selected($section === 'sin-matricula')>Sin matrícula en {{ $year->year }}</option>
                </select>
            @endif
            <button type="submit" class="btn btn-line btn-sm">Filtrar</button>
            @if (request()->hasAny(['q', 'seccion']))
                <a class="btn-link" href="{{ route('admin.people.students.index', ['lectivo' => request('lectivo')]) }}">Quitar filtros</a>
            @endif
        </form>

        @if ($students->isEmpty())
            <p class="empty panel">Ningún estudiante coincide con la búsqueda.</p>
        @else
            <p class="lead-sm list-count">{{ trans_choice(':count estudiante|:count estudiantes', $students->total()) }}</p>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Estudiante</th>
                            <th scope="col">Documento</th>
                            <th scope="col">Sección {{ $year?->year }}</th>
                            <th scope="col">Acudientes</th>
                            <th scope="col">Cuenta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            @php($enrollment = $student->enrollments->first())
                            <tr>
                                <td data-label="Estudiante"><a class="strong" href="{{ route('admin.people.students.edit', $student) }}">{{ $student->sortName() }}</a></td>
                                <td data-label="Documento" class="nowrap">{{ $student->documentLabel() }}</td>
                                <td data-label="Sección {{ $year?->year }}">
                                    @if ($enrollment)
                                        {{ $enrollment->section->label() }}
                                        @if ($enrollment->status === App\Enums\EnrollmentStatus::Withdrawn)
                                            <span class="badge badge-withdrawn">Retirado</span>
                                        @endif
                                    @else
                                        <span class="muted">Sin matrícula</span>
                                    @endif
                                </td>
                                <td data-label="Acudientes">
                                    @if ($student->guardians_count)
                                        {{ $student->guardians_count }}
                                    @else
                                        <span class="badge badge-in_review">Sin acudiente</span>
                                    @endif
                                </td>
                                <td data-label="Cuenta">
                                    @if ($student->user)
                                        {{ $student->user->must_change_password ? 'Temporal' : 'Activa' }}
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $students->links('partials.pagination') }}
        @endif
    @endif
</x-layouts.admin>
