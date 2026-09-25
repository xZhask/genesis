<?php

namespace App\Http\Requests\Admin;

use App\Enums\VolunteerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVolunteerApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('volunteer'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(VolunteerStatus::class)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['admin_note' => 'nota interna'];
    }
}
