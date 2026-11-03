<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

/** Dejar de recibir la tarjeta de cumpleaños, desde el enlace firmado del correo. */
class BirthdayMailController extends Controller
{
    public function show(): View
    {
        return view('family-mail.birthday-unsubscribe', ['done' => false]);
    }

    public function update(User $user): View
    {
        $user->forceFill(['birthday_emails' => false])->save();

        return view('family-mail.birthday-unsubscribe', ['done' => true]);
    }
}
