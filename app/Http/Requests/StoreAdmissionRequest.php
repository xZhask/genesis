<?php

namespace App\Http\Requests;

use App\Enums\GuardianRelationship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Grados ofrecidos, tomados de la configuración de niveles. */
    public static function grades(): array
    {
        return collect(config('school.levels'))->pluck('grades')->flatten()->all();
    }

    protected function prepareForValidation(): void
    {
        $phone = preg_replace('/[\s\-().]/', '', (string) $this->input('guardian_phone'));

        $this->merge([
            'guardian_phone' => $phone,
            'guardian_email' => $this->filled('guardian_email') ? mb_strtolower(trim($this->input('guardian_email'))) : null,
            'phone_has_whatsapp' => $this->boolean('phone_has_whatsapp'),
        ]);
    }

    public function rules(): array
    {
        return [
            'student_first_names' => ['required', 'string', 'max:80'],
            'student_last_names' => ['required', 'string', 'max:80'],
            'student_birth_date' => ['required', 'date', 'before:today', 'after:'.now()->subYears(20)->toDateString()],
            'grade' => ['required', Rule::in(self::grades())],
            'current_school' => ['nullable', 'string', 'max:120'],

            'guardian_name' => ['required', 'string', 'max:120'],
            'guardian_relationship' => ['required', Rule::enum(GuardianRelationship::class)],
            // Celular (3XX XXX XXXX) o fijo (60X XXX XXXX), con o sin +57
            'guardian_phone' => ['required', 'regex:/^(\+?57)?(3\d{9}|60\d{8})$/'],
            'phone_has_whatsapp' => ['boolean'],
            'guardian_email' => ['nullable', 'email', 'max:120'],

            'comments' => ['nullable', 'string', 'max:1000'],
            'privacy' => ['accepted'],
            // Campo trampa: las personas no lo ven; se revisa en el controlador
            'website' => ['nullable'],
        ];
    }

    public function attributes(): array
    {
        return [
            'student_first_names' => 'nombres del estudiante',
            'student_last_names' => 'apellidos del estudiante',
            'student_birth_date' => 'fecha de nacimiento',
            'grade' => 'grado',
            'current_school' => 'colegio actual',
            'guardian_name' => 'nombre del acudiente',
            'guardian_relationship' => 'parentesco',
            'guardian_phone' => 'celular',
            'guardian_email' => 'correo',
            'comments' => 'comentarios',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Escribe :attribute.',
            'grade.required' => 'Elige el grado al que aspira.',
            'guardian_relationship.required' => 'Elige el parentesco con el estudiante.',
            'student_birth_date.required' => 'Indica la fecha de nacimiento.',
            'max' => ':Attribute puede tener máximo :max caracteres.',
            'student_birth_date.date' => 'La fecha de nacimiento no es válida.',
            'student_birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'student_birth_date.after' => 'Revisa la fecha de nacimiento: parece demasiado antigua.',
            'grade.in' => 'Elige un grado de la lista.',
            'guardian_relationship.enum' => 'Elige un parentesco de la lista.',
            'guardian_phone.regex' => 'Escribe un celular de 10 dígitos que empiece por 3 (o un fijo que empiece por 60).',
            'guardian_email.email' => 'Revisa el correo: debe tener la forma nombre@dominio.com.',
            'privacy.accepted' => 'Para enviar la solicitud debes autorizar el tratamiento de datos.',
        ];
    }

    /** Datos listos para guardar, con el celular en formato +57XXXXXXXXXX. */
    public function admissionData(): array
    {
        $data = $this->safe()->except(['privacy', 'website']);
        $data['guardian_phone'] = '+57'.substr($data['guardian_phone'], -10);

        return $data;
    }
}
