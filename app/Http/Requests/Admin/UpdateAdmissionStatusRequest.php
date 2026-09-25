<?php

namespace App\Http\Requests\Admin;

use App\Enums\AdmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdmissionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('admission'));
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(AdmissionStatus::class),
                Rule::notIn([$this->route('admission')->status->value]),
            ],
            'interview_at' => [
                'nullable',
                Rule::requiredIf($this->input('status') === AdmissionStatus::InterviewScheduled->value),
                'date',
            ],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.not_in' => 'La solicitud ya está en ese estado.',
            'interview_at.required' => 'Indica la fecha y la hora de la entrevista.',
        ];
    }
}
