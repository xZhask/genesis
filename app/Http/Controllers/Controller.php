<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Cada acción se autoriza con su Policy ($this->authorize), además del middleware de rol.
    use AuthorizesRequests;
}
