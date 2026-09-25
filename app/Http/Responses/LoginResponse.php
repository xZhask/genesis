<?php

namespace App\Http\Responses;

use App\Enums\Role;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/** Después de ingresar, cada rol va a su zona. */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $home = match ($request->user()->role) {
            Role::Admin => route('admin.dashboard'),
            // Los portales de docente, estudiante y acudiente llegan en la fase 2.
            default => route('portal'),
        };

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended($home);
    }
}
