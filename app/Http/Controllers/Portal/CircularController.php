<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Circulares en el portal del acudiente: las públicas y las dirigidas a sus familias. */
class CircularController extends Controller
{
    public function index(Request $request): View
    {
        $guardian = $request->user()->guardian;
        abort_unless($guardian, 403);

        return view('portal.guardian.circulars', ['circulars' => Resource::circularsFor($guardian)->take(40)]);
    }
}
