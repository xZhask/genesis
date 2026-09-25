<?php

namespace App\Http\Requests\Admin\People;

use App\Enums\DocumentType;
use App\Enums\GuardianRelationship;
use App\Models\Guardian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Vincula un acudiente a un estudiante. Si ya existe un acudiente con ese
 * documento (por ejemplo, de un hermano), se vincula el mismo y no se piden
 * sus datos otra vez. Los campos llevan el prefijo guardian_ porque el
 * formulario comparte página con los datos del estudiante.
 */
class LinkGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('student'));
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('guardian_document_number')) {
            $this->merge(['guardian_document_number' => DocumentType::normalize($this->input('guardian_document_number'))]);
        }
        $this->merge(['is_primary' => $this->boolean('is_primary')]);
    }

    public function existingGuardian(): ?Guardian
    {
        return $this->filled('guardian_document_number')
            ? Guardian::firstWhere('document_number', $this->input('guardian_document_number'))
            : null;
    }

    public function rules(): array
    {
        $new = $this->existingGuardian() ? 'nullable' : 'required';

        return [
            'guardian_document_type' => [$new, Rule::enum(DocumentType::class)],
            'guardian_document_number' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]{3,20}$/'],
            'guardian_first_names' => [$new, 'string', 'max:80'],
            'guardian_last_names' => [$new, 'string', 'max:80'],
            'guardian_phone' => ['nullable', 'string', 'max:30', 'regex:/^[\d\s+()\-]{7,30}$/'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'relationship' => ['required', Rule::enum(GuardianRelationship::class)],
            'is_primary' => ['boolean'],
        ];
    }

    /** Datos para crear el acudiente nuevo. */
    public function guardianData(): array
    {
        return [
            'document_type' => $this->input('guardian_document_type'),
            'document_number' => $this->input('guardian_document_number'),
            'first_names' => $this->input('guardian_first_names'),
            'last_names' => $this->input('guardian_last_names'),
            'phone' => $this->input('guardian_phone'),
            'email' => $this->input('guardian_email') ? mb_strtolower($this->input('guardian_email')) : null,
        ];
    }

    public function attributes(): array
    {
        return [
            'guardian_document_type' => 'tipo de documento',
            'guardian_document_number' => 'número de documento',
            'guardian_first_names' => 'nombres',
            'guardian_last_names' => 'apellidos',
            'guardian_phone' => 'teléfono',
            'guardian_email' => 'correo',
            'relationship' => 'parentesco',
        ];
    }

    public function messages(): array
    {
        return [
            'guardian_document_number.regex' => 'Escribe solo letras y números (por ejemplo, 1045678901).',
            'guardian_phone.regex' => 'Escribe un teléfono válido, por ejemplo 321 797 5579.',
        ];
    }
}
