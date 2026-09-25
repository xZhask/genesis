<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** El acudiente pide cambiar su teléfono o su correo (queda pendiente). */
class ContactUpdateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()->guardian;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/\s+/', ' ', trim((string) $this->input('phone'))),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[\d\s+()\-]{7,30}$/'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
        ];
    }

    public function attributes(): array
    {
        return ['phone' => 'teléfono', 'email' => 'correo'];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Escribe un teléfono válido, por ejemplo 321 797 5579.',
            'email.unique' => 'Ese correo ya lo usa otra cuenta del portal. Escribe otro o comunícate con el colegio.',
        ];
    }
}
