<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Enums\DocumentType;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    public function boot(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Vistas Blade propias
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));

        // Se ingresa con el número de documento o con el correo. Las cuentas
        // desactivadas por el colegio no pueden ingresar.
        Fortify::authenticateUsing(function (Request $request) {
            $login = trim((string) $request->input('login'));
            $user = str_contains($login, '@')
                ? User::where('email', Str::lower($login))->first()
                : User::where('document_number', DocumentType::normalize($login))->first();

            if ($user && $user->is_active && Hash::check($request->input('password'), $user->password)) {
                $user->forceFill(['last_login_at' => now()])->saveQuietly();

                return $user;
            }

            return null;
        });

        // Máximo 5 intentos por minuto por documento (o correo) e IP
        RateLimiter::for('login', function (Request $request) {
            // "1.045.678" y "1045678" cuentan como el mismo intento
            $login = (string) $request->input(Fortify::username());
            $login = str_contains($login, '@') ? Str::lower($login) : DocumentType::normalize($login);
            $throttleKey = Str::transliterate($login.'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
