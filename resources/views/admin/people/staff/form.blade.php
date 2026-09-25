<x-layouts.admin :title="$user->exists ? $user->name : 'Nueva cuenta'">
    <p class="back"><a href="{{ route('admin.people.staff.index') }}">← Docentes y administración</a></p>
    <div class="admin-head">
        <h1>{{ $user->exists ? $user->name : 'Nueva cuenta del personal' }}</h1>
        @if ($user->exists)
            <p class="lead-sm">{{ $user->role->label() }}</p>
        @endif
    </div>

    @include('admin.people.partials.tabs')

    <div class="admin-grid detail">
        <section class="panel" aria-labelledby="datos-title">
            <h2 id="datos-title">Datos de la cuenta</h2>
            <form class="form compact" method="POST" action="{{ $user->exists ? route('admin.people.staff.update', $user) : route('admin.people.staff.store') }}" novalidate data-form>
                @csrf
                @if ($user->exists)
                    @method('PUT')
                @endif
                <x-field name="name" label="Nombre completo">
                    <input id="name" name="name" type="text" maxlength="120" autocomplete="off" value="{{ old('name', $user->name) }}">
                </x-field>
                <x-field name="document_number" label="Número de documento" hint="Con este número ingresa al portal.">
                    <input id="document_number" name="document_number" type="text" inputmode="numeric" maxlength="20" autocomplete="off"
                        value="{{ old('document_number', $user->document_number) }}" aria-describedby="document_number-hint">
                </x-field>
                <x-field name="email" label="Correo" optional hint="Para recuperar la contraseña. También sirve para ingresar.">
                    <input id="email" name="email" type="email" inputmode="email" autocomplete="off" value="{{ old('email', $user->email) }}" aria-describedby="email-hint">
                </x-field>
                <fieldset class="plain-fieldset @error('role') has-error @enderror">
                    <legend>Rol</legend>
                    <label class="check">
                        <input type="radio" name="role" value="teacher" @checked(old('role', $user->role->value) === 'teacher')>
                        <span><b>Docente</b> · registra asistencia y notas de las materias y secciones que se le asignen.</span>
                    </label>
                    <label class="check">
                        <input type="radio" name="role" value="admin" @checked(old('role', $user->role->value) === 'admin')>
                        <span><b>Administración</b> · acceso completo al panel: contenido, personas, estructura académica y cierre de periodos.</span>
                    </label>
                    @error('role')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </fieldset>
                @unless ($user->exists)
                    <p class="hint">Se genera una contraseña temporal para entregarle; al ingresar deberá crear la suya.</p>
                @endunless
                <button type="submit" class="btn btn-azul" data-submit data-loading-text="Guardando…"><span>{{ $user->exists ? 'Guardar cambios' : 'Crear cuenta' }}</span></button>
            </form>
        </section>

        @if ($user->exists)
            <div class="stack">
                @include('admin.people.partials.account', ['user' => $user, 'createRoute' => null, 'cannot' => null])

                @if ($user->guardian)
                    <section class="panel" aria-labelledby="acudiente-title">
                        <h2 id="acudiente-title">También es acudiente</h2>
                        <p class="hint">Usa esta misma cuenta para ver a sus acudidos:
                            @foreach ($user->guardian->students as $student)
                                {{ $student->fullName() }}{{ $loop->last ? '.' : ',' }}
                            @endforeach
                        </p>
                        <a class="btn-link" href="{{ route('admin.people.guardians.edit', $user->guardian) }}">Ver ficha de acudiente</a>
                    </section>
                @endif
            </div>
        @endif
    </div>
</x-layouts.admin>
