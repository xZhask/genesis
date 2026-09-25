<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

/** Las noticias solo las gestiona el admin. */
class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }
}
