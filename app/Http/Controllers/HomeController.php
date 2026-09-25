<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Post;
use App\Support\DemoContent;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $demo = DemoContent::enabled();

        // La galería se consultará a su modelo cuando exista su módulo. Mientras
        // tanto, vacía (la sección no se muestra) o contenido de ejemplo en local.
        return view('home', [
            'demo' => $demo,
            'levels' => config('school.levels'),
            'heroPhotos' => $demo ? DemoContent::heroPhotos() : config('school.hero_photos'),
            'posts' => Post::published()->latest('published_at')->limit(3)->get(),
            'events' => Event::upcoming()->limit(3)->get(),
            'photos' => $demo ? DemoContent::photos() : collect(),
            'support' => $demo ? DemoContent::support() : config('school.support'),
        ]);
    }
}
