<?php

use App\Enums\Role;
use App\Models\User;
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
