<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-settings');
    }

    protected function prepareForValidation(): void
    {
        // "$ 111.000" → 111000
        $costs = collect($this->input('costs', []))->map(fn ($cost) => [
            'label' => trim((string) ($cost['label'] ?? '')),
            'enrollment' => preg_replace('/\D/', '', (string) ($cost['enrollment'] ?? '')),
            'monthly' => preg_replace('/\D/', '', (string) ($cost['monthly'] ?? '')),
        ])->all();

        $this->merge([
            'costs' => $costs,
            'requirements' => $this->lines('requirements'),
            'office_hours' => $this->lines('office_hours'),
            'whatsapp_enabled' => $this->boolean('whatsapp_enabled'),
        ]);
    }

    /** Textarea con un elemento por línea → lista sin líneas vacías. */
    private function lines(string $field): array
    {
        return collect(preg_split('/\R/', (string) $this->input($field)))
            ->map(fn ($line) => trim(ltrim(trim($line), '-•*')))
            ->filter()
            ->values()
            ->all();
    }

    public function rules(): array
    {
        $groups = count(config('school.admissions.costs'));

        return [
            'school_year' => ['required', 'integer', 'between:2026,2100'],
            'admissions_badge' => ['nullable', 'string', 'max:60'],
            'costs' => ['required', 'array', "size:{$groups}"],
            'costs.*.label' => ['required', 'string', 'max:60'],
            'costs.*.enrollment' => ['required', 'integer', 'min:0', 'max:99999999'],
            'costs.*.monthly' => ['required', 'integer', 'min:0', 'max:99999999'],
            'requirements' => ['required', 'array', 'min:1', 'max:20'],
            'requirements.*' => ['string', 'max:250'],
            'response_time' => ['required', 'string', 'max:40'],
            'admissions_notify_email' => ['required', 'email', 'max:120'],
            'support_notify_email' => ['required', 'email', 'max:120'],
            'office_hours' => ['array', 'max:6'],
            'office_hours.*' => ['string', 'max:120'],
            'whatsapp_enabled' => ['boolean'],
            'report_approval' => ['nullable', 'string', 'max:160'],
            'report_nit' => ['nullable', 'string', 'max:30'],
            'report_campus' => ['nullable', 'string', 'max:60'],
            'report_rector' => ['nullable', 'string', 'max:120'],
            'alert_absences' => ['required', 'integer', 'between:1,30'],
        ];
    }

    /** Valores listos para Settings::save() (clave de config => valor). */
    public function settings(): array
    {
        $current = config('school.admissions.costs');

        return [
            'admissions.school_year' => (int) $this->validated('school_year'),
            'admissions_badge' => $this->validated('admissions_badge'),
            // Se conservan los niveles de cada grupo; solo cambian nombre y valores
            'admissions.costs' => collect($this->validated('costs'))->map(fn ($cost, $i) => [
                'label' => $cost['label'],
                'levels' => $current[$i]['levels'],
                'enrollment' => (int) $cost['enrollment'],
                'monthly' => (int) $cost['monthly'],
            ])->all(),
            'admissions.requirements' => $this->validated('requirements'),
            'admissions.response_time' => $this->validated('response_time'),
            'admissions.notify_email' => mb_strtolower($this->validated('admissions_notify_email')),
            'support.notify_email' => mb_strtolower($this->validated('support_notify_email')),
            'office_hours' => $this->validated('office_hours', []),
            'whatsapp.enabled' => $this->validated('whatsapp_enabled'),
            'report_card.approval' => trim((string) $this->validated('report_approval')),
            'report_card.nit' => trim((string) $this->validated('report_nit')),
            'report_card.campus' => trim((string) $this->validated('report_campus')),
            'report_card.rector' => trim((string) $this->validated('report_rector')),
            'alerts.absences' => (int) $this->validated('alert_absences'),
        ];
    }

    public function attributes(): array
    {
        return [
            'school_year' => 'año lectivo',
            'admissions_badge' => 'aviso de la portada',
            'costs.*.label' => 'nombre del grupo',
            'costs.*.enrollment' => 'matrícula',
            'costs.*.monthly' => 'mensualidad',
            'requirements' => 'documentos',
            'requirements.*' => 'documento',
            'response_time' => 'tiempo de respuesta',
            'admissions_notify_email' => 'correo de avisos de pre-inscripción',
            'support_notify_email' => 'correo de avisos de voluntariado',
            'office_hours' => 'horarios de atención',
            'office_hours.*' => 'horario',
            'report_approval' => 'resolución de aprobación',
            'report_nit' => 'NIT',
            'report_campus' => 'sede',
            'report_rector' => 'nombre de rectoría',
            'alert_absences' => 'faltas para la alerta',
        ];
    }

    public function messages(): array
    {
        return [
            'requirements.required' => 'Escribe al menos un documento.',
            'costs.*.enrollment.required' => 'Escribe el valor de la matrícula.',
            'costs.*.monthly.required' => 'Escribe el valor de la mensualidad.',
            'email' => 'Revisa el correo: debe tener la forma nombre@dominio.com.',
        ];
    }
}
