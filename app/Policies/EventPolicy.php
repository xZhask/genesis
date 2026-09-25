<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

/** Los eventos del calendario solo los gestiona el admin. */
class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Event $post): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Event $post): bool
    {
        return $user->isAdmin();
    }
}
