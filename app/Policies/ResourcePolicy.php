<?php

namespace App\Policies;

use App\Models\Resource;
use App\Models\User;

/** Los recursos para acudientes solo los gestiona el admin. */
class ResourcePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Resource $resource): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Resource $resource): bool
    {
        return $user->isAdmin();
    }
}
