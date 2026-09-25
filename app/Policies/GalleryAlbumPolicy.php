<?php

namespace App\Policies;

use App\Models\GalleryAlbum;
use App\Models\User;

/** La galería (álbumes y sus fotos) solo la gestiona el admin. */
class GalleryAlbumPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, GalleryAlbum $album): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, GalleryAlbum $album): bool
    {
        return $user->isAdmin();
    }
}
