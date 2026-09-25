<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Cuentas del portal creadas por el admin. No hay registro público: el
 * colegio entrega a cada persona su documento y una contraseña temporal,
 * que debe cambiar al primer ingreso.
 */
class Accounts
{
    /**
     * Contraseña temporal fácil de dictar por teléfono o escribir en papel:
     * dos sílabas, guion y cuatro números ("tamo-4827"), sin letras que se
     * confundan. Solo sirve hasta el primer ingreso.
     */
    public static function temporaryPassword(): string
    {
        $consonants = 'bcdfgjklmnprstvz';
        $vowels = 'aeiou';
        $word = '';
        for ($i = 0; $i < 4; $i++) {
            $set = $i % 2 === 0 ? $consonants : $vowels;
            $word .= $set[random_int(0, strlen($set) - 1)];
        }

        return $word.'-'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Crea la cuenta de un estudiante o acudiente con su documento.
     * Si ya existe una cuenta con ese documento (por ejemplo, una docente
     * que también es acudiente), se vincula esa cuenta y no se crea otra.
     *
     * @return array{user: User, password: ?string}
     */
    public static function createFor(Student|Guardian $person): array
    {
        if ($person->user_id) {
            throw new InvalidArgumentException('La persona ya tiene cuenta.');
        }

        return DB::transaction(function () use ($person) {
            $existing = User::where('document_number', $person->document_number)->first();

            if ($existing) {
                // Una cuenta solo puede ser de un estudiante y de un acudiente a la vez
                if ($person instanceof Student || $existing->guardian()->exists() || $existing->student()->exists()) {
                    throw new InvalidArgumentException("El documento {$person->document_number} ya tiene una cuenta ({$existing->role->label()}).");
                }
                $person->user()->associate($existing)->save();

                return ['user' => $existing, 'password' => null];
            }

            $password = static::temporaryPassword();
            $user = new User([
                'name' => $person->fullName(),
                'document_number' => $person->document_number,
                'email' => $person instanceof Guardian ? static::freeEmail($person->email) : null,
                'password' => $password,
            ]);
            $user->role = $person instanceof Student ? Role::Student : Role::Guardian;
            $user->must_change_password = true;
            $user->save();

            $person->user()->associate($user)->save();

            return ['user' => $user, 'password' => $password];
        });
    }

    /** Nueva contraseña temporal (la persona la cambia al ingresar). */
    public static function resetPassword(User $user): string
    {
        $password = static::temporaryPassword();
        $user->forceFill(['password' => $password, 'must_change_password' => true])->save();

        return $password;
    }

    /** El correo sirve para recuperar la contraseña; si otra cuenta ya lo usa, se omite. */
    private static function freeEmail(?string $email): ?string
    {
        return filled($email) && ! User::where('email', mb_strtolower($email))->exists() ? $email : null;
    }

    /** Datos para la hoja de acceso que el admin imprime o dicta. */
    public static function slip(User $user, string $password): array
    {
        return [
            'name' => $user->name,
            'role' => $user->role->label(),
            'login' => $user->document_number ?? $user->email,
            'password' => $password,
        ];
    }
}
