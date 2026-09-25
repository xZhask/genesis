<?php

namespace App\Http\Requests\Admin\People;

use Illuminate\Foundation\Http\FormRequest;

class GuardianRequest extends FormRequest
{
    use PersonRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('guardian'));
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDocument();
    }

    public function rules(): array
    {
        return [
            ...$this->personRules('guardians', $this->route('guardian')->id),
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[\d\s+()\-]{7,30}$/'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return $this->personAttributes();
    }

    public function messages(): array
    {
        return [...$this->personMessages(), 'phone.regex' => 'Escribe un teléfono válido, por ejemplo 321 797 5579.'];
    }
}
