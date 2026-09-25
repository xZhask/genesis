{{-- Datos del estudiante (crear y editar) --}}
<div class="field-group even">
    <x-field name="document_type" label="Tipo de documento">
        <select id="document_type" name="document_type">
            @foreach (App\Enums\DocumentType::cases() as $type)
                <option value="{{ $type->value }}" @selected(old('document_type', $student->document_type?->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </x-field>
    <x-field name="document_number" label="Número de documento" hint="Con él ingresa al portal desde {{ config('school.academic.student_accounts_from') }}.">
        <input id="document_number" name="document_number" type="text" inputmode="numeric" maxlength="20" autocomplete="off"
            value="{{ old('document_number', $student->document_number) }}" aria-describedby="document_number-hint">
    </x-field>
</div>
<div class="field-group even">
    <x-field name="first_names" label="Nombres">
        <input id="first_names" name="first_names" type="text" maxlength="80" autocomplete="off" value="{{ old('first_names', $student->first_names) }}">
    </x-field>
    <x-field name="last_names" label="Apellidos">
        <input id="last_names" name="last_names" type="text" maxlength="80" autocomplete="off" value="{{ old('last_names', $student->last_names) }}">
    </x-field>
</div>
<x-field name="birth_date" label="Fecha de nacimiento" optional>
    <input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date', $student->birth_date?->toDateString()) }}">
</x-field>
