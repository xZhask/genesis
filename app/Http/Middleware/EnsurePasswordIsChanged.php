<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Las cuentas que crea el colegio llevan una contraseña temporal: hasta
 * cambiarla, la persona no puede usar el panel ni el portal.
 */
class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Debes cambiar tu contraseña temporal.'], 403)
                : redirect()->route('password.change');
        }

        return $next($request);
    }
}
