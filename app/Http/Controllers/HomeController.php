<?php

namespace App\Http\Controllers;

use App\Support\DemoContent;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $demo = DemoContent::enabled();

        // Noticias, eventos y galería se consultarán a sus modelos cuando existan
        // los módulos del admin. Mientras tanto, vacío (la sección no se muestra)
        // o contenido de ejemplo en local.
        return view('home', [
            'demo' => $demo,
            'levels' => config('school.levels'),
            'heroPhotos' => $demo ? DemoContent::heroPhotos() : config('school.hero_photos'),
            'posts' => $demo ? DemoContent::posts() : collect(),
            'events' => $demo ? DemoContent::events() : collect(),
            'photos' => $demo ? DemoContent::photos() : collect(),
            'support' => $demo ? DemoContent::support() : config('school.support'),
        ]);
    }
}
