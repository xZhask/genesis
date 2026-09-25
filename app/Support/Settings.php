<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Ajustes editables desde el admin. Los valores por defecto están en
 * config/school.php; lo guardado en la tabla settings los reemplaza al
 * arrancar la aplicación, así el resto del código sigue leyendo config().
 */
class Settings
{
    /** Claves (dentro de config('school')) que el admin puede cambiar. */
    public const KEYS = [
        'admissions.school_year',
        'admissions.costs',
        'admissions.requirements',
        'admissions.response_time',
        'admissions.notify_email',
        'admissions_badge',
        'office_hours',
        'whatsapp.enabled',
        'support.notify_email',
    ];

    private const CACHE_KEY = 'school-settings';

    /** Aplica lo guardado sobre config('school'). */
    public static function apply(): void
    {
        try {
            $saved = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::pluck('value', 'key')->all());
        } catch (Throwable) {
            // Tabla aún sin migrar (instalación nueva): se usan los valores por defecto
            return;
        }

        foreach (array_intersect_key($saved, array_flip(self::KEYS)) as $key => $value) {
            config(["school.{$key}" => $value]);
        }
    }

    /** @param  array<string, mixed>  $values  clave => valor */
    public static function save(array $values, User $user): void
    {
        foreach (array_intersect_key($values, array_flip(self::KEYS)) as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $user->id]);
        }

        Cache::forget(self::CACHE_KEY);
        self::apply();
    }

    /** Última modificación, para mostrarla en el admin. */
    public static function lastChange(): ?Setting
    {
        return Setting::with('editor')->latest('updated_at')->first();
    }
}
