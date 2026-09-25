<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Costos, requisitos, horarios… editados en /admin/configuracion
        Settings::apply();
        Gate::define('manage-settings', fn (User $user) => $user->isAdmin());
        // Estructura académica: años, periodos (cierre y reapertura), secciones, materias y plan de estudios
        Gate::define('manage-academic', fn (User $user) => $user->isAdmin());
        // Personal, cuentas del portal e importación (estudiantes y acudientes usan sus Policies)
        Gate::define('manage-people', fn (User $user) => $user->isAdmin());

        // Direcciones del admin en español: /admin/noticias/nueva, /admin/noticias/{post}/editar
        Route::resourceVerbs(['create' => 'nueva', 'edit' => 'editar']);

        // Contraseñas: mínimo 8 caracteres con letras y números
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        // Formularios públicos: máximo 5 solicitudes por hora desde la misma IP
        RateLimiter::for('admissions', fn (Request $request) => Limit::perHour(5)->by($request->ip()));
        RateLimiter::for('volunteers', fn (Request $request) => Limit::perHour(5)->by($request->ip()));

        // Formatos de fecha y hora en pantalla (convenciones del proyecto)
        // "18 de septiembre de 2026"
        Carbon::macro('longDate', fn () => $this->translatedFormat('j \d\e F \d\e Y'));
        // "18 de septiembre"
        Carbon::macro('dayMonth', fn () => $this->translatedFormat('j \d\e F'));
        // "7:00 a. m."
        Carbon::macro('shortTime', fn () => $this->translatedFormat('g:i a'));
    }
}
