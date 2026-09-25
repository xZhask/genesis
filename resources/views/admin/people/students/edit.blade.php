@use('App\Enums\EnrollmentStatus')

<x-layouts.admin :title="$student->fullName()">
    <p class="back"><a href="{{ route('admin.people.students.index') }}">← Estudiantes</a></p>
    <div class="admin-head">
        <h1>{{ $student->fullName() }}</h1>
        <p class="lead-sm">
            {{ $student->documentLabel() }}
            @if ($enrollment)
                · {{ $enrollment->section->label() }} ({{ $year->year }})
            @endif
        </p>
    </div>

    @include('admin.people.partials.tabs')

    <div class="admin-grid detail">
        <div class="stack">
            <section class="panel" aria-labelledby="acudientes-title">
                <h2 id="acudientes-title">Acudientes</h2>
                @if ($student->guardians->isEmpty())
                    <p class="notice">Todavía no tiene acudientes. Vincula al menos uno: es quien verá sus notas y asistencia.</p>
                @else
                    <ul class="guardian-links">
                        @foreach ($student->guardians as $guardian)
                            <li>
                                <div class="guardian-link-head">
                                    <a class="strong" href="{{ route('admin.people.guardians.edit', $guardian) }}">{{ $guardian->fullName() }}</a>
                                    @if ($guardian->pivot->is_primary)
                                        <span class="badge badge-accepted">Principal</span>
                                    @endif
                                    <small>{{ $guardian->documentLabel() }}@if ($guardian->phone) · {{ $guardian->phone }}@endif · {{ $guardian->user ? 'Con cuenta' : 'Sin cuenta' }}</small>
                                </div>
                                <div class="guardian-link-actions">
                                    <form method="POST" action="{{ route('admin.people.students.guardians.update', [$student, $guardian]) }}" class="inline-form">
                                        @csrf
                                        @method('PUT')
                                        <label class="sr-only" for="rel-{{ $guardian->id }}">Parentesco de {{ $guardian->fullName() }}</label>
                                        <select id="rel-{{ $guardian->id }}" name="relationship">
                                            @foreach ($relationships as $relationship)
                                                <option value="{{ $relationship->value }}" @selected($guardian->pivot->relationship === $relationship)>{{ $relationship->label() }}</option>
                                            @endforeach
                                        </select>
                                        @unless ($guardian->pivot->is_primary)
                                            <label class="check">
                                                <input type="checkbox" name="is_primary" value="1">
                                                <span>Principal</span>
                                            </label>
                                        @endunless
                                        <button type="submit" class="btn btn-line btn-sm">Guardar</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.people.students.guardians.destroy', [$student, $guardian]) }}"
                                        data-confirm="¿Desvincular a {{ $guardian->fullName() }}? Dejará de ver la información de {{ $student->first_names }}.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="link-danger">Desvincular</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <details class="link-guardian" @if ($student->guardians->isEmpty() || $errors->hasAny(['guardian_document_number', 'guardian_document_type', 'guardian_first_names', 'guardian_last_names', 'guardian_phone', 'guardian_email', 'relationship'])) open @endif>
                    <summary>Vincular acudiente</summary>
                    <form class="form compact" method="POST" action="{{ route('admin.people.students.guardians.store', $student) }}" novalidate data-form>
                        @csrf
                        <x-field name="guardian_document_number" label="Número de documento"
                            hint="Si ya está registrado (por ejemplo, es acudiente de un hermano), basta con el documento y el parentesco.">
                            <input id="guardian_document_number" name="guardian_document_number" type="text" inputmode="numeric" maxlength="20" autocomplete="off"
                                value="{{ old('guardian_document_number') }}" aria-describedby="guardian_document_number-hint">
                        </x-field>
                        <x-field name="relationship" label="Parentesco">
                            <select id="relationship" name="relationship">
                                <option value="">Elige…</option>
                                @foreach ($relationships as $relationship)
                                    <option value="{{ $relationship->value }}" @selected(old('relationship') === $relationship->value)>{{ $relationship->label() }}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <fieldset class="plain-fieldset">
                            <legend>Si es un acudiente nuevo</legend>
                            <x-field name="guardian_document_type" label="Tipo de documento">
                                <select id="guardian_document_type" name="guardian_document_type">
                                    @foreach (App\Enums\DocumentType::cases() as $type)
                                        <option value="{{ $type->value }}" @selected(old('guardian_document_type', 'CC') === $type->value)>{{ $type->label() }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                            <div class="field-group even">
                                <x-field name="guardian_first_names" label="Nombres">
                                    <input id="guardian_first_names" name="guardian_first_names" type="text" maxlength="80" autocomplete="off" value="{{ old('guardian_first_names') }}">
                                </x-field>
                                <x-field name="guardian_last_names" label="Apellidos">
                                    <input id="guardian_last_names" name="guardian_last_names" type="text" maxlength="80" autocomplete="off" value="{{ old('guardian_last_names') }}">
                                </x-field>
                            </div>
                            <div class="field-group even">
                                <x-field name="guardian_phone" label="Teléfono" optional>
                                    <input id="guardian_phone" name="guardian_phone" type="tel" inputmode="tel" maxlength="30" autocomplete="off" value="{{ old('guardian_phone') }}">
                                </x-field>
                                <x-field name="guardian_email" label="Correo" optional>
                                    <input id="guardian_email" name="guardian_email" type="email" inputmode="email" autocomplete="off" value="{{ old('guardian_email') }}">
                                </x-field>
                            </div>
                        </fieldset>
                        @if ($student->guardians->isNotEmpty())
                            <label class="check">
                                <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary'))>
                                <span>Es el acudiente principal (a quien el colegio llama primero)</span>
                            </label>
                        @endif
                        <button type="submit" class="btn btn-azul btn-sm" data-submit data-loading-text="Vinculando…"><span>Vincular acudiente</span></button>
                    </form>
                </details>
            </section>

            <section class="panel" aria-labelledby="datos-title">
                <h2 id="datos-title">Datos del estudiante</h2>
                <form class="form compact" method="POST" action="{{ route('admin.people.students.update', $student) }}" novalidate data-form>
                    @csrf
                    @method('PUT')
                    @include('admin.people.students.partials.fields')
                    <button type="submit" class="btn btn-line" data-submit><span>Guardar datos</span></button>
                </form>
            </section>
        </div>

        <div class="stack">
            <section class="panel" aria-labelledby="matricula-title">
                <h2 id="matricula-title">Matrícula {{ $year?->year }}</h2>
                @if ($year && $sections->isNotEmpty())
                    <form class="form compact" method="POST" action="{{ route('admin.people.students.enroll', $student) }}" novalidate data-form>
                        @csrf
                        @method('PUT')
                        <x-field name="section_id" label="Sección">
                            @include('admin.people.partials.section-select', ['name' => 'section_id', 'selected' => old('section_id', $enrollment?->section_id), 'placeholder' => 'Elige la sección'])
                        </x-field>
                        <fieldset class="plain-fieldset">
                            <legend>Estado</legend>
                            @foreach (EnrollmentStatus::cases() as $status)
                                <label class="check">
                                    <input type="radio" name="status" value="{{ $status->value }}" @checked(old('status', $enrollment?->status->value ?? 'active') === $status->value)>
                                    <span>{{ $status->label() }}@if ($status === EnrollmentStatus::Withdrawn && $enrollment?->withdrawn_on) (desde el {{ $enrollment->withdrawn_on->longDate() }})@endif</span>
                                </label>
                            @endforeach
                        </fieldset>
                        <button type="submit" class="btn btn-line btn-sm" data-submit><span>{{ $enrollment ? 'Guardar matrícula' : 'Matricular' }}</span></button>
                    </form>
                @else
                    <p class="hint">Primero crea el año lectivo actual y sus secciones en <a href="{{ route('admin.academic.sections.index') }}">Académico</a>.</p>
                @endif

                @php $history = $student->enrollments->reject(fn ($e) => $e->school_year_id === $year?->id); @endphp
                @if ($history->isNotEmpty())
                    <h3>Años anteriores</h3>
                    <ul class="period-history">
                        @foreach ($history as $past)
                            <li>{{ $past->schoolYear->year }}: {{ $past->section->label() }} · {{ $past->status->label() }}</li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @php
                $closedPeriods = $student->enrollments
                    ->flatMap(fn ($e) => $e->schoolYear->periods->filter->isClosed());
            @endphp
            @if ($closedPeriods->isNotEmpty())
                <section class="panel" aria-labelledby="boletines-title">
                    <h2 id="boletines-title">Boletines</h2>
                    <div class="report-links">
                        @foreach ($closedPeriods as $closed)
                            <a class="btn btn-line btn-sm" href="{{ route('report-cards.student', [$student, $closed]) }}" target="_blank" rel="noopener">
                                <x-icon name="download" /> {{ $closed->schoolYear->year }} · P{{ $closed->number }}
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @include('admin.people.partials.account', [
                'user' => $student->user,
                'role' => App\Enums\Role::Student,
                'createRoute' => $canHaveAccount ? route('admin.people.accounts.student', $student) : null,
                'cannot' => "Los estudiantes tienen cuenta propia desde {$accountsFrom}. Mientras tanto, ingresa su acudiente.",
            ])

            <section class="panel danger-zone" aria-labelledby="eliminar-title">
                <h2 id="eliminar-title">Eliminar estudiante</h2>
                <p class="hint">Solo si se registró por error. Si dejó el colegio, márcalo como retirado en la matrícula.</p>
                <form method="POST" action="{{ route('admin.people.students.destroy', $student) }}"
                    data-confirm="¿Eliminar a {{ $student->fullName() }} con su matrícula, vínculos y cuenta? No se puede deshacer.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                </form>
            </section>
        </div>
    </div>
</x-layouts.admin>
