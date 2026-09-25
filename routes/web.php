<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\SupportController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/admisiones', [AdmissionController::class, 'show'])->name('admissions');
Route::post('/admisiones/solicitud', [AdmissionController::class, 'store'])
    ->middleware('throttle:admissions')
    ->name('admissions.store');
Route::get('/admisiones/solicitud-enviada', [AdmissionController::class, 'thanks'])->name('admissions.thanks');

Route::view('/politica-de-datos', 'pages.privacy')->name('privacy');

// Nosotros incluye los niveles educativos (secciones #preescolar, #primaria, #secundaria)
Route::view('/nosotros', 'pages.about')->name('about');
Route::permanentRedirect('/niveles', '/nosotros#niveles');

// Calendario y noticias (una sola opción de menú; el calendario es la entrada principal)
Route::get('/calendario', [CalendarController::class, 'index'])->name('calendar');
Route::get('/calendario/{event}/agregar.ics', [CalendarController::class, 'ics'])->name('calendar.ics');
Route::get('/noticias', [NewsController::class, 'index'])->name('news');
Route::get('/noticias/{post}', [NewsController::class, 'show'])->name('news.show');

Route::get('/galeria', [GalleryController::class, 'index'])->name('gallery');
Route::get('/galeria/{album}', [GalleryController::class, 'show'])->name('gallery.show');

// Recursos para acudientes (públicos: solo información general)
Route::get('/recursos', [ResourceController::class, 'index'])->name('resources');
Route::get('/recursos/circulares', [ResourceController::class, 'circulars'])->name('resources.circulars');

// Apóyanos: donaciones, voluntariado y aliados
Route::get('/apoyanos', [SupportController::class, 'show'])->name('support');
Route::post('/apoyanos/voluntariado', [SupportController::class, 'storeVolunteer'])
    ->middleware('throttle:volunteers')
    ->name('support.volunteer');

// Inicio de sesión y recuperación de contraseña: Fortify (config/fortify.php)

// Portales de docente, estudiante y acudiente: fase 2. Por ahora, un aviso.
Route::view('/portal', 'portal.coming-soon')->middleware('auth')->name('portal');

// Panel admin
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('/solicitudes', [Admin\AdmissionRequestController::class, 'index'])->name('admissions.index');
    Route::get('/solicitudes/{admission}', [Admin\AdmissionRequestController::class, 'show'])->name('admissions.show');
    Route::put('/solicitudes/{admission}/estado', [Admin\AdmissionRequestController::class, 'updateStatus'])->name('admissions.status');

    Route::resource('noticias', Admin\PostController::class)
        ->parameters(['noticias' => 'post'])
        ->names('posts')
        ->except('show');

    Route::resource('eventos', Admin\EventController::class)
        ->parameters(['eventos' => 'event'])
        ->names('events')
        ->except('show');

    Route::resource('galeria', Admin\GalleryAlbumController::class)
        ->parameters(['galeria' => 'album'])
        ->names('albums')
        ->except('show');
    Route::post('/galeria/{album}/fotos', [Admin\GalleryPhotoController::class, 'store'])->name('albums.photos.store');
    Route::put('/galeria/{album}/fotos', [Admin\GalleryPhotoController::class, 'update'])->name('albums.photos.update');

    Route::resource('recursos', Admin\ResourceController::class)
        ->parameters(['recursos' => 'resource'])
        ->names('resources')
        ->except('show');

    // Apóyanos: voluntarios (datos personales), cuentas, donantes y testimonios
    Route::prefix('apoyanos')->group(function () {
        Route::redirect('/', '/admin/apoyanos/voluntarios')->name('support');

        Route::get('/voluntarios', [Admin\VolunteerApplicationController::class, 'index'])->name('volunteers.index');
        Route::get('/voluntarios/{volunteer}', [Admin\VolunteerApplicationController::class, 'show'])->name('volunteers.show');
        Route::put('/voluntarios/{volunteer}', [Admin\VolunteerApplicationController::class, 'update'])->name('volunteers.update');

        Route::resource('cuentas', Admin\DonationAccountController::class)
            ->parameters(['cuentas' => 'account'])
            ->names('accounts')
            ->except('show');
        Route::resource('donantes', Admin\DonorController::class)
            ->parameters(['donantes' => 'donor'])
            ->names('donors')
            ->except('show');
        Route::resource('testimonios', Admin\TestimonialController::class)
            ->parameters(['testimonios' => 'testimonial'])
            ->names('testimonials')
            ->except('show');
    });
});
