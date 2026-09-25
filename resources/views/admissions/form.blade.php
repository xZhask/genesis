@php
    // aria-describedby con la ayuda y el error de cada campo
    $describedBy = fn (string $name, bool $hint = false) => collect([
        $hint ? "{$name}-hint" : null,
        $errors->has($name) ? "{$name}-error" : null,
    ])->filter()->join(' ');
    $invalid = fn (string $name) => $errors->has($name) ? 'aria-invalid=true' : '';
@endphp

<form class="form" method="POST" action="{{ route('admissions.store') }}" novalidate data-form>
    @csrf

    @if ($errors->any())
        <div class="error-summary" role="alert" tabindex="-1" data-error-summary>
            <h3>Revisa {{ $errors->count() === 1 ? 'este dato' : 'estos '.$errors->count().' datos' }} para enviar la solicitud</h3>
            <ul>
                @foreach ($errors->keys() as $field)
                    <li><a href="#{{ $field }}">{{ $errors->first($field) }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif

    <fieldset>
        <legend>Datos del estudiante</legend>

        <div class="row-2">
            <x-field name="student_first_names" label="Nombres">
                <input id="student_first_names" name="student_first_names" type="text" value="{{ old('student_first_names') }}"
                    maxlength="80" autocomplete="off" aria-describedby="{{ $describedBy('student_first_names') }}" {{ $invalid('student_first_names') }}>
            </x-field>
            <x-field name="student_last_names" label="Apellidos">
                <input id="student_last_names" name="student_last_names" type="text" value="{{ old('student_last_names') }}"
                    maxlength="80" autocomplete="off" aria-describedby="{{ $describedBy('student_last_names') }}" {{ $invalid('student_last_names') }}>
            </x-field>
        </div>

        <div class="row-2">
            <x-field name="student_birth_date" label="Fecha de nacimiento">
                <input id="student_birth_date" name="student_birth_date" type="date" value="{{ old('student_birth_date') }}"
                    max="{{ now()->subDay()->toDateString() }}" aria-describedby="{{ $describedBy('student_birth_date') }}" {{ $invalid('student_birth_date') }}>
            </x-field>
            <x-field name="grade" label="Grado al que aspira en {{ $schoolYear }}">
                <select id="grade" name="grade" aria-describedby="{{ $describedBy('grade') }}" {{ $invalid('grade') }}>
                    <option value="">Elige un grado</option>
                    @foreach ($levels as $level)
                        <optgroup label="{{ $level['name'] }}">
                            @foreach ($level['grades'] as $grade)
                                <option value="{{ $grade }}" @selected(old('grade') === $grade)>{{ $grade }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </x-field>
        </div>

        <x-field name="current_school" label="Colegio o jardín actual" optional hint="Si todavía no estudia, déjalo en blanco.">
            <input id="current_school" name="current_school" type="text" value="{{ old('current_school') }}" maxlength="120"
                aria-describedby="{{ $describedBy('current_school', true) }}" {{ $invalid('current_school') }}>
        </x-field>
    </fieldset>

    <fieldset>
        <legend>Datos del acudiente</legend>

        <x-field name="guardian_name" label="Nombre completo">
            <input id="guardian_name" name="guardian_name" type="text" value="{{ old('guardian_name') }}" maxlength="120"
                autocomplete="name" aria-describedby="{{ $describedBy('guardian_name') }}" {{ $invalid('guardian_name') }}>
        </x-field>

        <fieldset @class(['field', 'choices', 'has-error' => $errors->has('guardian_relationship')])
            aria-describedby="{{ $describedBy('guardian_relationship') }}">
            <legend class="label">Parentesco con el estudiante</legend>
            <div class="pills" id="guardian_relationship" tabindex="-1">
                @foreach ($relationships as $relationship)
                    <label class="pill">
                        <input type="radio" name="guardian_relationship" value="{{ $relationship->value }}"
                            @checked(old('guardian_relationship') === $relationship->value)>
                        <span>{{ $relationship->label() }}</span>
                    </label>
                @endforeach
            </div>
            @error('guardian_relationship')
                <p class="error" id="guardian_relationship-error">{{ $message }}</p>
            @enderror
        </fieldset>

        <div class="row-2">
            <x-field name="guardian_phone" label="Celular" hint="Ejemplo: 321 797 5579">
                <input id="guardian_phone" name="guardian_phone" type="tel" inputmode="tel" value="{{ old('guardian_phone') }}"
                    maxlength="20" autocomplete="tel-national" aria-describedby="{{ $describedBy('guardian_phone', true) }}" {{ $invalid('guardian_phone') }}>
                <label class="check">
                    <input type="checkbox" name="phone_has_whatsapp" value="1" @checked(old('phone_has_whatsapp', true))>
                    <span>Este número tiene WhatsApp</span>
                </label>
            </x-field>
            <x-field name="guardian_email" label="Correo" optional hint="Te enviaremos una copia de la solicitud.">
                <input id="guardian_email" name="guardian_email" type="email" inputmode="email" value="{{ old('guardian_email') }}"
                    maxlength="120" autocomplete="email" aria-describedby="{{ $describedBy('guardian_email', true) }}" {{ $invalid('guardian_email') }}>
            </x-field>
        </div>
    </fieldset>

    <fieldset>
        <legend>Algo más</legend>

        <x-field name="comments" label="¿Algo que debamos saber?" optional
            hint="Por ejemplo, hermanos en el colegio, necesidades de apoyo o el mejor horario para llamarte.">
            <textarea id="comments" name="comments" rows="4" maxlength="1000"
                aria-describedby="{{ $describedBy('comments', true) }}" {{ $invalid('comments') }}>{{ old('comments') }}</textarea>
        </x-field>

        {{-- Campo trampa: oculto para las personas, los robots lo llenan --}}
        <div class="hp" aria-hidden="true">
            <label for="website">No llenes este campo</label>
            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
        </div>

        <div @class(['field', 'consent', 'has-error' => $errors->has('privacy')])>
            <label class="check">
                <input id="privacy" type="checkbox" name="privacy" value="1" @checked(old('privacy'))
                    aria-describedby="{{ $describedBy('privacy') }}" {{ $invalid('privacy') }}>
                <span>
                    Autorizo al {{ config('school.name') }} a tratar mis datos y los del estudiante para gestionar esta solicitud,
                    según la <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Política de tratamiento de datos</a>.
                </span>
            </label>
            @error('privacy')
                <p class="error" id="privacy-error">{{ $message }}</p>
            @enderror
        </div>
    </fieldset>

    <button type="submit" class="btn btn-sol btn-block" data-submit data-loading-text="Enviando…">
        <x-icon name="form" /> <span>Enviar solicitud</span>
    </button>
</form>
