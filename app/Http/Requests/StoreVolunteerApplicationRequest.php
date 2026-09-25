<?php

namespace App\Http\Requests;

use App\Enums\VolunteerArea;
use App\Enums\VolunteerAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVolunteerApplicationRequest extends FormRequest
{
    /** Los errores vuelven al formulario, no al inicio de la página. */
    protected $redirect = '/apoyanos#voluntariado';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/[\s\-().]/', '', (string) $this->input('phone')),
            'email' => $this->filled('email') ? mb_strtolower(trim($this->input('email'))) : null,
            'phone_has_whatsapp' => $this->boolean('phone_has_whatsapp'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            // Celular (3XX XXX XXXX) o fijo (60X XXX XXXX), con o sin +57
            'phone' => ['required', 'regex:/^(\+?57)?(3\d{9}|60\d{8})$/'],
            'phone_has_whatsapp' => ['boolean'],
            'email' => ['nullable', 'email', 'max:120'],
            'areas' => ['required', 'array', 'min:1'],
            'areas.*' => [Rule::enum(VolunteerArea::class)],
            'availability' => ['required', Rule::enum(VolunteerAvailability::class)],
            'message' => ['nullable', 'string', 'max:1000'],
            'privacy' => ['accepted'],
            // Campo trampa: las personas no lo ven; se revisa en el controlador
            'website' => ['nullable'],
        ];
    }

    public function volunteerData(): array
    {
        $data = $this->safe()->except(['privacy', 'website']);
        $data['phone'] = '+57'.substr($data['phone'], -10);

        return $data;
    }

    public function attributes(): array
    {
        return [
            'name' => 'tu nombre',
            'phone' => 'tu celular',
            'email' => 'correo',
            'message' => 'mensaje',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Escribe :attribute.',
            'max' => ':Attribute puede tener máximo :max caracteres.',
            'phone.regex' => 'Escribe un celular de 10 dígitos que empiece por 3 (o un fijo que empiece por 60).',
            'email.email' => 'Revisa el correo: debe tener la forma nombre@dominio.com.',
            'areas.required' => 'Elige al menos una forma de ayudar.',
            'areas.*.enum' => 'Elige una opción de la lista.',
            'availability.required' => 'Cuéntanos cuándo puedes ayudar.',
            'availability.enum' => 'Elige una opción de la lista.',
            'privacy.accepted' => 'Para enviar tus datos debes autorizar su tratamiento.',
        ];
    }
}
