<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

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
        // Formatos de fecha y hora en pantalla (convenciones del proyecto)
        // "18 de septiembre de 2026"
        Carbon::macro('longDate', fn () => $this->translatedFormat('j \d\e F \d\e Y'));
        // "18 de septiembre"
        Carbon::macro('dayMonth', fn () => $this->translatedFormat('j \d\e F'));
        // "7:00 a. m."
        Carbon::macro('shortTime', fn () => $this->translatedFormat('g:i a'));
    }
}
