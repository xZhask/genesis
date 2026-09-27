<?php

namespace App\Http\Requests\Portal;

use App\Enums\FeedbackType;
use App\Models\FeedbackMessage;
use App\Models\Section;
use App\Models\Student;
use App\Models\TeacherAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Mensaje del buzón. «about» llega como "estudiante:asignación" (0 = el
 * colegio en general) y solo vale para los acudidos propios y sus clases.
 */
class FeedbackRequest extends FormRequest
{
    private ?Collection $topics = null;

    public function authorize(): bool
    {
        return FeedbackMessage::authorRoleOf($this->user()) !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['body' => trim((string) $this->input('body'))]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(FeedbackType::class)],
            'about' => ['required', 'string', 'regex:/^\d+:\d+$/'],
            'body' => ['required', 'string', 'min:10', 'max:'.FeedbackMessage::MAX_LENGTH],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->has('about') || ! $this->filled('about')) {
                return;
            }
            if (! $this->topic()) {
                $validator->errors()->add('about', 'Elige una de las opciones de la lista.');
            }
        }];
    }

    /**
     * Estudiante, grupo y clase elegidos, comprobados contra lo que la cuenta puede ver.
     *
     * @return array{student: Student, section: ?Section, assignment: ?TeacherAssignment}|null
     */
    public function topic(): ?array
    {
        [$studentId, $assignmentId] = array_map('intval', explode(':', (string) $this->input('about')) + [1 => 0]);

        $this->topics ??= FeedbackMessage::topicsFor($this->user());
        $topic = $this->topics->first(fn (array $t) => $t['student']->id === $studentId);
        if (! $topic) {
            return null;
        }
        $assignment = $assignmentId ? $topic['assignments']->firstWhere('id', $assignmentId) : null;
        if ($assignmentId && ! $assignment) {
            return null;
        }

        return ['student' => $topic['student'], 'section' => $topic['section'], 'assignment' => $assignment];
    }

    public function attributes(): array
    {
        return ['type' => 'tipo de mensaje', 'about' => 'sobre qué es', 'body' => 'mensaje'];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Elige si es una sugerencia, una queja, una observación o un reconocimiento.',
            'about.required' => 'Elige sobre qué es tu mensaje.',
            'body.required' => 'Escribe tu mensaje.',
            'body.min' => 'Cuéntanos un poco más (al menos 10 caracteres).',
        ];
    }
}
