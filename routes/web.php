<?php

use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/admisiones', [AdmissionController::class, 'show'])->name('admissions');
Route::post('/admisiones/solicitud', [AdmissionController::class, 'store'])
    ->middleware('throttle:admissions')
    ->name('admissions.store');
Route::get('/admisiones/solicitud-enviada', [AdmissionController::class, 'thanks'])->name('admissions.thanks');

Route::view('/politica-de-datos', 'pages.privacy')->name('privacy');

// Páginas temporales: cada una se reemplaza al construir su sección.
$comingSoon = [
    'about' => ['/nosotros', 'Nosotros'],
    'levels' => ['/niveles', 'Niveles educativos'],
    'news' => ['/noticias', 'Noticias'],
    'calendar' => ['/calendario', 'Calendario escolar'],
    'gallery' => ['/galeria', 'Galería'],
    'resources' => ['/recursos', 'Recursos para acudientes'],
    'support' => ['/apoyanos', 'Apóyanos'],
    // Fortify registrará la ruta real de inicio de sesión en la fase del portal.
    'login' => ['/ingresar', 'Portal académico'],
];

foreach ($comingSoon as $name => [$uri, $title]) {
    Route::view($uri, 'pages.coming-soon', ['title' => $title])->name($name);
}
