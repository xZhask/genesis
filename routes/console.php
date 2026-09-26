<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use App\Support\FamilyMail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

// Hosting compartido: no hay workers permanentes. El cron ejecuta
// `php artisan schedule:run` cada minuto y este comando vacía la cola
// (correos) y termina.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();

// Correos a las familias (fase 3): a las 6:00 a. m. se anotan los
// recordatorios de los eventos próximos y cada minuto se envía una tanda de
// la bandeja de salida, sin pasar el tope diario.
Schedule::command('app:queue-event-reminders')->dailyAt('06:00');
Schedule::command('app:deliver-family-mail')->everyMinute()->withoutOverlapping();

Artisan::command('app:queue-event-reminders', function () {
    $days = (int) config('school.family_mail.reminder_days');
    // Desde mañana hasta el día del recordatorio: un evento creado con menos
    // anticipación igual recibe su aviso (y nunca dos, por la bandeja)
    $events = Event::where('send_reminder', true)
        ->whereBetween('starts_at', [today()->addDay(), today()->addDays($days)->endOfDay()])
        ->get();

    $queued = $events->sum(fn (Event $event) => FamilyMail::queueEventReminder($event));
    $this->info("Recordatorios anotados: {$queued} ({$events->count()} eventos).");
})->purpose('Anotar los recordatorios de eventos para las familias');

Artisan::command('app:deliver-family-mail', function () {
    $sent = FamilyMail::deliver();
    if ($sent) {
        $this->info("Correos enviados a familias: {$sent}.");
    }
})->purpose('Enviar una tanda de correos a las familias');

// Crea la cuenta de administración en producción (el seeder es solo para local).
Artisan::command('app:create-admin {email} {name}', function (string $email, string $name) {
    $password = $this->secret('Contraseña (mínimo 8 caracteres, con letras y números)');

    $validator = Validator::make(
        ['email' => $email, 'password' => $password],
        ['email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', Password::defaults()]],
    );

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    $user = new User(['name' => $name, 'email' => strtolower($email), 'password' => $password]);
    $user->role = Role::Admin;
    $user->save();

    $this->info("Cuenta de administración creada para {$user->email}.");
})->purpose('Crear una cuenta de administración');
