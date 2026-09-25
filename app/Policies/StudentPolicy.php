<?php

namespace App\Policies;

use App\Enums\EnrollmentStatus;
use App\Enums\Role;
use App\Models\SchoolYear;
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
}
