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
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            // Cada foto nueva necesita autorización; la del nombre no la cubre
            'photo_consent' => [$this->hasFile('photo') ? 'accepted' : 'nullable'],
            'remove_photo' => ['boolean'],
            'is_visible' => ['boolean'],
            'position' => ['integer', 'between:0,999'],
            // Se pide al crear; queda registrada la fecha
            'consent' => [$this->route('donor')?->consent_at ? 'nullable' : 'accepted'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre', 'description' => 'descripción', 'website' => 'página web', 'photo' => 'foto'];
    }

    public function messages(): array
    {
        return [
            'consent.accepted' => 'Confirma que el donante autorizó publicar su nombre.',
            'photo_consent.accepted' => 'Confirma que el donante autorizó publicar esta foto.',
            'photo.image' => 'La foto debe ser una imagen JPG, PNG o WebP.',
            'photo.mimes' => 'La foto debe ser una imagen JPG, PNG o WebP.',
            'photo.max' => 'La foto pesa más de 8 MB. Usa una más liviana.',
            'website.url' => 'Escribe la dirección completa, por ejemplo https://ejemplo.com.',
        ];
    }
}
