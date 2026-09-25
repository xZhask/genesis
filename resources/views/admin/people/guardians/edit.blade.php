<x-layouts.admin :title="$guardian->fullName()">
    <p class="back"><a href="{{ route('admin.people.guardians.index') }}">← Acudientes</a></p>
    <div class="admin-head">
        <h1>{{ $guardian->fullName() }}</h1>
        <p class="lead-sm">Acudiente · {{ $guardian->documentLabel() }}</p>
    </div>

    @include('admin.people.partials.tabs')

    <div class="admin-grid detail">
        <section class="panel" aria-labelledby="datos-title">
            <h2 id="datos-title">Datos del acudiente</h2>
            <form class="form compact" method="POST" action="{{ route('admin.people.guardians.update', $guardian) }}" novalidate data-form>
                @csrf
                @method('PUT')
                <div class="field-group even">
                    <x-field name="document_type" label="Tipo de documento">
                        <select id="document_type" name="document_type">
                            @foreach (App\Enums\DocumentType::cases() as $type)
                                <option value="{{ $type->value }}" @selected(old('document_type', $guardian->document_type->value) === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field name="document_number" label="Número de documento" hint="Con este número ingresa al portal.">
                        <input id="document_number" name="document_number" type="text" inputmode="numeric" maxlength="20" autocomplete="off"
                            value="{{ old('document_number', $guardian->document_number) }}" aria-describedby="document_number-hint">
                    </x-field>
                </div>
                <div class="field-group even">
                    <x-field name="first_names" label="Nombres">
                        <input id="first_names" name="first_names" type="text" maxlength="80" autocomplete="off" value="{{ old('first_names', $guardian->first_names) }}">
                    </x-field>
                    <x-field name="last_names" label="Apellidos">
                        <input id="last_names" name="last_names" type="text" maxlength="80" autocomplete="off" value="{{ old('last_names', $guardian->last_names) }}">
                    </x-field>
                </div>
                <div class="field-group even">
                    <x-field name="phone" label="Teléfono" optional>
                        <input id="phone" name="phone" type="tel" inputmode="tel" maxlength="30" autocomplete="off" value="{{ old('phone', $guardian->phone) }}">
                    </x-field>
                    <x-field name="email" label="Correo" optional>
                        <input id="email" name="email" type="email" inputmode="email" autocomplete="off" value="{{ old('email', $guardian->email) }}">
                    </x-field>
                </div>
                <button type="submit" class="btn btn-line" data-submit><span>Guardar datos</span></button>
            </form>
        </section>

        <div class="stack">
            <section class="panel" aria-labelledby="acudidos-title">
                <h2 id="acudidos-title">Acudidos</h2>
                @forelse ($guardian->students as $student)
                    @php($enrollment = $student->enrollments->first())
                    <a class="list-row" href="{{ route('admin.people.students.edit', $student) }}">
                        <span>
                            <strong>{{ $student->fullName() }}</strong>
                            @if ($student->pivot->is_primary)
                                <span class="badge badge-accepted">Principal</span>
                            @endif
                            <small>{{ $student->pivot->relationship->label() }} · {{ $enrollment?->section->label() ?? 'Sin matrícula este año' }}</small>
                        </span>
                        <span aria-hidden="true">→</span>
                    </a>
                @empty
                    <p class="hint">No tiene acudidos. Se vinculan desde la ficha del estudiante.</p>
                @endforelse
            </section>

            @include('admin.people.partials.account', [
                'user' => $guardian->user,
                'role' => App\Enums\Role::Guardian,
                'createRoute' => route('admin.people.accounts.guardian', $guardian),
                'cannot' => null,
            ])

            <section class="panel danger-zone" aria-labelledby="eliminar-title">
                <h2 id="eliminar-title">Eliminar acudiente</h2>
                <p class="hint">Se quitan sus vínculos y su cuenta del portal. Los estudiantes no se eliminan.</p>
                <form method="POST" action="{{ route('admin.people.guardians.destroy', $guardian) }}"
                    data-confirm="¿Eliminar a {{ $guardian->fullName() }}? No se puede deshacer.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                </form>
            </section>
        </div>
    </div>
</x-layouts.admin>
