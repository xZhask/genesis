<?php

namespace App\Policies;

use App\Models\AdmissionRequest;
use App\Models\User;

/** Las solicitudes de pre-inscripción solo las gestiona el admin. */
class AdmissionRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, AdmissionRequest $admission): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, AdmissionRequest $admission): bool
    {
        return $user->isAdmin();
    }
}
