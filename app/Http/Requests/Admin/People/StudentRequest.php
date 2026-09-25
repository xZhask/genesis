<?php

namespace App\Http\Requests\Admin\People;

use App\Models\SchoolYear;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    use PersonRules;

    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student
            ? $this->user()->can('update', $student)
            : $this->user()->can('create', Student::class);
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDocument();
    }

    public function rules(): array
    {
        $rules = [
            ...$this->personRules('students', $this->route('student')?->id),
            'birth_date' => ['nullable', 'date', 'after:1990-01-01', 'before:today'],
        ];

        // Al crear se puede matricular de una vez en el año actual
        if (! $this->route('student')) {
            $rules['section_id'] = ['nullable', Rule::exists('sections', 'id')->where('school_year_id', SchoolYear::current()?->id)];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [...$this->personAttributes(), 'section_id' => 'sección'];
    }

    public function messages(): array
    {
        return $this->personMessages();
    }
}
