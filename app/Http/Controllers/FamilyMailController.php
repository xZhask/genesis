<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * «Dejar de recibir estos avisos» desde el pie de cada correo, sin iniciar
 * sesión: el enlace va firmado. Se pide confirmar con un botón porque
 * algunos programas de correo abren los enlaces solos al revisarlos.
 */
class FamilyMailController extends Controller
{
    public function show(Guardian $guardian): View
    {
        return view('family-mail.unsubscribe', ['guardian' => $guardian, 'done' => false]);
    }

    public function update(Request $request, Guardian $guardian): View
    {
        $guardian->forceFill(['email_notifications' => false])->save();

        return view('family-mail.unsubscribe', ['guardian' => $guardian, 'done' => true]);
    }
}
