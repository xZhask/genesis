<?php

namespace App\Http\Requests\Admin;

use App\Models\Testimonial;
use Illuminate\Foundation\Http\FormRequest;

class TestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $testimonial = $this->route('testimonial');

        return $testimonial
            ? $this->user()->can('update', $testimonial)
            : $this->user()->can('create', Testimonial::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            // Las comillas las pone la página
            'quote' => trim((string) $this->input('quote'), " \t\n\r\"“”«»"),
            'is_visible' => $this->boolean('is_visible'),
            'position' => (int) $this->input('position'),
        ]);
    }

    public function rules(): array
    {
        return [
            'quote' => ['required', 'string', 'max:400'],
            'author' => ['required', 'string', 'max:80'],
            'role' => ['nullable', 'string', 'max:80'],
            'is_visible' => ['boolean'],
            'position' => ['integer', 'between:0,999'],
            'consent' => [$this->route('testimonial')?->consent_at ? 'nullable' : 'accepted'],
        ];
    }

    public function attributes(): array
    {
        return ['quote' => 'testimonio', 'author' => 'nombre', 'role' => 'relación con el colegio'];
    }

    public function messages(): array
    {
        return [
            'consent.accepted' => 'Confirma que la persona autorizó publicar su testimonio y su nombre.',
        ];
    }
}
