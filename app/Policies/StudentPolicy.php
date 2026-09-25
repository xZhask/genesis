<?php

namespace App\Policies;

use App\Enums\EnrollmentStatus;
use App\Enums\Role;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;

/**
 * El admin gestiona a todos los estudiantes. Consultar a uno (perfil y
 * asistencia) lo puede hacer además:
 * - el propio estudiante;
 * - sus acudientes vinculados;
 * - un docente que le dicta alguna materia este año, o su director de grupo.
 */
class StudentPolicy extends AdminOnlyPolicy
{
    public function view(User $user, mixed $student): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->role === Role::Student) {
            return $student->user_id === $user->id;
        }

        if ($user->guardian && $user->guardian->students()->whereKey($student->id)->exists()) {
            return true;
        }

        if ($user->role === Role::Teacher) {
            $section = $student->enrollments()
                ->where('school_year_id', SchoolYear::current()?->id)
                ->where('status', EnrollmentStatus::Active)
                ->value('section_id');

            return $section && (
                $user->assignments()->where('section_id', $section)->exists()
                || $user->homeroomSections()->whereKey($section)->exists()
            );
        }

        return false;
    }

    /**
     * Boletín en PDF: el propio estudiante, sus acudientes, el director de
     * grupo de la sección en la que estaba matriculado ese año y el admin.
     * (Un docente de una sola materia no descarga el boletín completo.)
     */
    public function downloadReportCard(User $user, Student $student, Enrollment $enrollment): bool
    {
        if ($enrollment->student_id !== $student->id || ! $user->is_active) {
            return false;
        }

        return $user->isAdmin()
            || ($user->role === Role::Student && $student->user_id === $user->id)
            || ($user->guardian && $user->guardian->students()->whereKey($student->id)->exists())
            || ($user->role === Role::Teacher && $enrollment->section->homeroom_teacher_id === $user->id);
    }
}
