<?php

use Illuminate\Support\Facades\Schedule;

// Hosting compartido: no hay workers permanentes. El cron ejecuta
// `php artisan schedule:run` cada minuto y este comando vacía la cola
// (correos) y termina.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
