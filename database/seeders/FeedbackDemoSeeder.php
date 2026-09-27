<?php

namespace Database\Seeders;

use App\Enums\FeedbackType;
use App\Enums\Role;
use App\Models\FeedbackMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Mensajes FICTICIOS del buzón para desarrollo local (nunca en producción):
 * uno respondido de Andrés y dos abiertos de Marta, para ver el portal y el
 * panel con contenido.
 */
class FeedbackDemoSeeder extends Seeder
{
    public function run(): void
    {
        $student = User::firstWhere('email', 'estudiante@genesis.test');
        $guardian = User::firstWhere('email', 'acudiente@genesis.test');
        $reviewer = User::feedbackReviewers()->first();
        if (! $student?->student || ! $guardian?->guardian || ! $reviewer) {
            return;
        }

        $andres = FeedbackMessage::topicsFor($student)->first();
        $math = $andres['assignments']->first(fn ($a) => $a->subject->name === 'Matemáticas') ?? $andres['assignments']->first();
        $this->create($student, Role::Student, $andres, $math, FeedbackType::Suggestion,
            'Sería bueno que en Matemáticas nos dejaran los ejercicios de práctica en el portal para repasar antes de las evaluaciones.',
            now()->subDays(9))
            ->answer($reviewer, 'Gracias por la idea, Andrés. Lo hablamos con el área de Matemáticas y desde el próximo periodo se compartirán las guías de repaso.');

        $topics = FeedbackMessage::topicsFor($guardian);
        $lucia = $topics->first(fn ($t) => $t['student']->first_names === 'Lucía') ?? $topics->first();
        $this->create($guardian, Role::Guardian, $lucia, null, FeedbackType::Observation,
            'En la salida de la tarde hay mucho desorden en la puerta principal y los niños pequeños quedan entre los carros. ¿Se podría organizar una fila de recogida?',
            now()->subDays(2));
        $this->create($guardian, Role::Guardian, $andres, null, FeedbackType::Recognition,
            'Queremos agradecer la jornada ambiental del mes pasado. Andrés llegó muy motivado a sembrar en casa.',
            now()->subHours(20));
    }

    private function create(User $author, Role $role, array $topic, $assignment, FeedbackType $type, string $body, $at): FeedbackMessage
    {
        $message = new FeedbackMessage(['type' => $type, 'body' => $body]);
        $message->forceFill([
            'user_id' => $author->id,
            'author_role' => $role,
            'student_id' => $topic['student']->id,
            'section_id' => $topic['section']?->id,
            'teacher_assignment_id' => $assignment?->id,
            'subject_id' => $assignment?->subject_id,
            'teacher_id' => $assignment?->teacher_id,
            'created_at' => $at,
            'updated_at' => $at,
        ])->save();

        return $message;
    }
}
