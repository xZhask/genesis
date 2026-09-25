<?php

namespace App\Http\Requests\Admin;

use App\Models\Donor;
use Illuminate\Foundation\Http\FormRequest;

class DonorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $donor = $this->route('donor');

        return $donor
            ? $this->user()->can('update', $donor)
            : $this->user()->can('create', Donor::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_visible' => $this->boolean('is_visible'),
            'position' => (int) $this->input('position'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:160'],
            'website' => ['nullable', 'url:https,http', 'max:255'],
            'is_visible' => ['boolean'],
            'position' => ['integer', 'between:0,999'],
            // Se pide al crear; queda registrada la fecha
            'consent' => [$this->route('donor')?->consent_at ? 'nullable' : 'accepted'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre', 'description' => 'descripción', 'website' => 'página web'];
    }

    public function messages(): array
    {
        return [
            'consent.accepted' => 'Confirma que el donante autorizó publicar su nombre.',
            'website.url' => 'Escribe la dirección completa, por ejemplo https://ejemplo.com.',
        ];
    }
}
