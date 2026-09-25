<?php

namespace App\Policies;

use App\Models\User;

/**
 * Contenido que solo gestiona el admin. Las políticas de Apóyanos la
 * extienden; si un día otro rol necesita permisos, se sobrescribe ahí.
 */
abstract class AdminOnlyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, mixed $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, mixed $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, mixed $model): bool
    {
        return $user->isAdmin();
    }
}
