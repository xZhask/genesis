<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/** Después de ingresar, cada rol va a su zona. */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();
        // Con contraseña temporal, lo primero es cambiarla
        $home = $user->must_change_password ? route('password.change') : $user->homeUrl();

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended($home);
    }
}
