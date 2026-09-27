<?php

namespace App\Http\Requests\Admin\People;

use App\Enums\DocumentType;
use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Docentes y administración: cuentas del personal del colegio. */
class StaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-people');
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('document_number')) {
            $this->merge(['document_number' => DocumentType::normalize($this->input('document_number'))]);
        }
        if ($this->filled('email')) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        $id = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'document_number' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]{3,20}$/', Rule::unique('users')->ignore($id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'role' => ['required', Rule::in([Role::Teacher->value, Role::Admin->value])],
            'can_review_feedback' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre completo', 'document_number' => 'número de documento', 'email' => 'correo', 'role' => 'rol'];
    }

    public function messages(): array
    {
        return [
            'document_number.regex' => 'Escribe solo letras y números (por ejemplo, 1045678901).',
            'document_number.unique' => 'Ya hay una cuenta con ese documento.',
            'email.unique' => 'Ya hay una cuenta con ese correo.',
        ];
    }
}
