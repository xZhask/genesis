<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe un grupo de rutas a uno o varios roles. Uso: ->middleware('role:admin')
 * Cualquier otro rol recibe 403, incluso si escribe la URL a mano.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = array_map(fn (string $role) => Role::from($role), $roles);

        abort_unless($user && $user->is_active && $user->hasRole(...$allowed), 403);

        return $next($request);
    }
}
