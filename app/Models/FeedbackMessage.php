<?php

namespace App\Models;

use App\Enums\FeedbackStatus;
use App\Enums\FeedbackType;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Mensaje del buzón de sugerencias. Lo escribe un estudiante o un acudiente
 * y solo lo leen los admins autorizados (User::canReviewFeedback): el
 * docente mencionado no lo ve. No es anónimo para quien lo revisa.
 */
class FeedbackMessage extends Model
{
    public const MAX_LENGTH = 1000;

    protected $fillable = ['type', 'body'];

    protected function casts(): array
    {
        return [
            'type' => FeedbackType::class,
            'status' => FeedbackStatus::class,
            'author_role' => Role::class,
            'replied_at' => 'datetime',
            'reply_seen_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function replier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    /** Sin responder todavía (recibidos y en revisión). */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNot('status', FeedbackStatus::Answered);
    }

    /** Respuestas que quien escribió todavía no ha visto. */
    public function scopeUnseenReplies(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id)->where('status', FeedbackStatus::Answered)->whereNull('reply_seen_at');
    }

    /**
     * Sobre quién y sobre qué clases puede escribir cada cuenta: el
     * estudiante, sobre sí mismo; el acudiente, sobre cada acudido. Las
     * clases son las de su grupo en el año actual.
     *
     * @return Collection<int, array{student: Student, section: ?Section, assignments: Collection<int, TeacherAssignment>}>
     */
    public static function topicsFor(User $user): Collection
    {
        $students = match (true) {
            $user->role === Role::Student => collect([$user->student])->filter(),
            (bool) $user->guardian => $user->guardian->students,
            default => collect(),
        };
        $year = SchoolYear::current();

        return $students->map(function (Student $student) use ($year) {
            $section = $student->enrollmentFor($year)?->section;

            return [
                'student' => $student,
                'section' => $section,
                'assignments' => $section
                    ? $section->assignments()->with(['subject', 'teacher'])->get()->sortBy('subject.name')->values()
                    : collect(),
            ];
        })->values();
    }

    /** Rol con el que escribe: un docente que también es acudiente escribe como acudiente. */
    public static function authorRoleOf(User $user): ?Role
    {
        return match (true) {
            $user->role === Role::Student && (bool) $user->student => Role::Student,
            $user->role !== Role::Admin && (bool) $user->guardian => Role::Guardian,
            default => null,
        };
    }

    /** "Estudiante de 7.° 1" o "Acudiente de Andrés Pérez Díaz (7.° 1)". */
    public function authorLabel(): string
    {
        $group = $this->section ? $this->section->label() : 'sin grupo';

        return $this->author_role === Role::Student
            ? "Estudiante de {$group}"
            : "Acudiente de {$this->student->fullName()} ({$group})";
    }

    /** "Matemáticas · Edgar Ojoalegre" o "El colegio en general". */
    public function aboutLabel(): string
    {
        if (! $this->subject) {
            return 'El colegio en general';
        }

        return $this->subject->name.($this->teacher ? ' · '.$this->teacher->name : '');
    }

    public function isAnswered(): bool
    {
        return $this->status === FeedbackStatus::Answered;
    }

    public function markInReview(): void
    {
        if ($this->status === FeedbackStatus::Received) {
            $this->forceFill(['status' => FeedbackStatus::InReview])->save();
        }
    }

    /** Responder también sirve para corregir la respuesta: quien escribió la vuelve a ver como nueva. */
    public function answer(User $reviewer, string $reply): void
    {
        $this->forceFill([
            'status' => FeedbackStatus::Answered,
            'reply' => trim($reply),
            'replied_by' => $reviewer->id,
            'replied_at' => now(),
            'reply_seen_at' => null,
        ])->save();
    }
}
