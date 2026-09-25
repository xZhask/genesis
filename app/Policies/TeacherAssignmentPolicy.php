<?php

namespace App\Policies;

use App\Models\TeacherAssignment;
use App\Models\User;

/** Un docente solo registra asistencia (y luego notas) de sus propias asignaciones. */
class TeacherAssignmentPolicy
{
    public function record(User $user, TeacherAssignment $assignment): bool
    {
        return $user->is_active && $assignment->teacher_id === $user->id;
    }
}
