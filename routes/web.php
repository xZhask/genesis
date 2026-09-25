<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Páginas temporales: cada una se reemplaza al construir su sección.
$comingSoon = [
    'about' => ['/nosotros', 'Nosotros'],
    'levels' => ['/niveles', 'Niveles educativos'],
    'admissions' => ['/admisiones', 'Admisiones'],
    'news' => ['/noticias', 'Noticias'],
    'calendar' => ['/calendario', 'Calendario escolar'],
    'gallery' => ['/galeria', 'Galería'],
    'resources' => ['/recursos', 'Recursos para acudientes'],
    'support' => ['/apoyanos', 'Apóyanos'],
    'privacy' => ['/politica-de-datos', 'Política de tratamiento de datos'],
    // Fortify registrará la ruta real de inicio de sesión en la fase del portal.
    'login' => ['/ingresar', 'Portal académico'],
];

foreach ($comingSoon as $name => [$uri, $title]) {
    Route::view($uri, 'pages.coming-soon', ['title' => $title])->name($name);
}
