<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\PasswordValidationRules;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Cambio de contraseña con la sesión iniciada. Obligatorio al primer ingreso
 * con una contraseña temporal (entonces no se pide la actual: la persona
 * acaba de escribirla).
 */
class PasswordChangeController extends Controller
{
    use PasswordValidationRules;

    public function edit(Request $request): View
    {
        return view('auth.change-password', ['forced' => $request->user()->must_change_password]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => $user->must_change_password ? ['nullable'] : ['required', 'current_password'],
            'password' => [...$this->passwordRules(), function ($attribute, $value, $fail) use ($user) {
                if (Hash::check($value, $user->password)) {
                    $fail('La contraseña nueva debe ser distinta de la actual.');
                }
            }],
        ], [
            'current_password.required' => 'Escribe tu contraseña actual.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
        ]);

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect($user->homeUrl())->with('status_message', 'Listo, tu contraseña quedó guardada.');
    }
}
