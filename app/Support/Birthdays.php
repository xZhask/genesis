<?php

namespace App\Support;

use App\Enums\EnrollmentStatus;
use App\Enums\Role;
use App\Mail\BirthdayGreetingMail;
use App\Models\BirthdayGreeting;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Cumpleaños de estudiantes matriculados y del personal activo: la lista del
 * admin y la tarjeta por correo. A cada estudiante le llega a su correo; si no
 * tiene, al de su acudiente (primero el principal). Nunca se muestra la edad
 * en la tarjeta.
 */
class Birthdays
{
    /**
     * @return Collection<int, array{kind: string, person: Student|User, name: string, detail: string, month: int, day: int, date: Carbon, days: int, url: string}>
     */
    public static function people(): Collection
    {
        return self::students()->map(fn (Student $s) => self::row(
            'student', $s, $s->sortName(), $s->enrollments->first()->section->label(), route('admin.people.students.edit', $s),
        ))->concat(self::staff()->map(fn (User $u) => self::row(
            'staff', $u, $u->name, $u->role->label(), route('admin.people.staff.edit', $u),
        )))->values();
    }

    /** Estudiantes con matrícula activa este año y fecha de nacimiento. */
    private static function students(): Collection
    {
        $year = SchoolYear::current();

        return $year ? self::enrolled($year)->whereNotNull('birth_date')
            ->with([
                'user',
                'guardians.user',
                'enrollments' => fn ($q) => $q->where('school_year_id', $year->id)->with('section.grade'),
            ])
            ->get() : collect();
    }

    private static function enrolled(SchoolYear $year): Builder
    {
        return Student::whereHas('enrollments', fn (Builder $q) => $q
            ->where('school_year_id', $year->id)
            ->where('status', EnrollmentStatus::Active));
    }

    /** Docentes y administración con cuenta activa y fecha de nacimiento. */
    private static function staff(): Collection
    {
        return self::activeStaff()->whereNotNull('birth_date')->get();
    }

    private static function activeStaff(): Builder
    {
        return User::whereIn('role', [Role::Teacher, Role::Admin])->where('is_active', true);
    }

    private static function row(string $kind, Student|User $person, string $name, string $detail, string $url): array
    {
        $date = AcademicAlerts::nextBirthday($person->birth_date);

        return [
            'kind' => $kind,
            'person' => $person,
            'name' => $name,
            'detail' => $detail,
            'month' => $person->birth_date->month,
            'day' => $person->birth_date->day,
            'date' => $date,
            'days' => (int) today()->diffInDays($date),
            'url' => $url,
        ];
    }

    /** A quién le faltan datos: sin fecha no aparecen ni reciben tarjeta. */
    public static function missingDates(): array
    {
        $year = SchoolYear::current();

        return [
            'students' => $year ? self::enrolled($year)->whereNull('birth_date')->count() : 0,
            'staff' => self::activeStaff()->whereNull('birth_date')->count(),
        ];
    }

    public static function todayCount(): int
    {
        return self::people()->where('days', 0)->count();
    }

    /**
     * A qué correo le llega la tarjeta de esta persona; null si a ninguno.
     *
     * @return array{email: string, guardian: ?Guardian}|null
     */
    public static function recipient(Student|User $person): ?array
    {
        $user = $person instanceof User ? $person : $person->user;

        if ($user?->is_active && $user->email) {
            return $user->birthday_emails ? ['email' => $user->email, 'guardian' => null] : null;
        }

        if ($person instanceof Student) {
            $guardian = $person->guardians->first(fn (Guardian $g) => FamilyMail::acceptsNotices($g));

            return $guardian ? ['email' => FamilyMail::address($guardian), 'guardian' => $guardian] : null;
        }

        return null;
    }

    /** Anota las tarjetas de hoy; se envían con la bandeja de cada minuto. */
    public static function queueToday(): int
    {
        // No hace falta guardar para siempre quién cumple años y a qué correo se avisó
        BirthdayGreeting::where('celebrated_on', '<', today()->subYear()->toDateString())->delete();

        if (! config('school.birthdays.enabled')) {
            return 0;
        }

        $rows = self::people()->where('days', 0)
            ->map(function (array $row) {
                $recipient = self::recipient($row['person']);

                return $recipient ? [
                    'celebrant_type' => $row['person']->getMorphClass(),
                    'celebrant_id' => $row['person']->getKey(),
                    'celebrated_on' => today()->toDateString(),
                    'email' => $recipient['email'],
                    'guardian_id' => $recipient['guardian']?->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ] : null;
            })
            ->filter()
            ->values();

        // El índice único descarta las que ya estaban
        return $rows->chunk(500)->sum(fn ($chunk) => BirthdayGreeting::insertOrIgnore($chunk->all()));
    }

    /**
     * Envía las tarjetas pendientes, sin pasar el tope diario de correos.
     *
     * @return int correos enviados
     */
    public static function deliver(int $batch): int
    {
        // Una felicitación atrasada ya no sirve: lleva la fecha del día
        BirthdayGreeting::whereNull('sent_at')->where('celebrated_on', '<', today()->toDateString())->delete();

        $limit = min($batch, FamilyMail::remainingToday());
        if ($limit === 0 || ! config('school.birthdays.enabled')) {
            return 0;
        }

        $sent = 0;
        foreach (BirthdayGreeting::whereNull('sent_at')->orderBy('id')->limit($limit)->with(['celebrant', 'guardian'])->get() as $greeting) {
            if (! $greeting->celebrant) {
                $greeting->delete();

                continue;
            }

            try {
                Mail::to($greeting->email)->send(new BirthdayGreetingMail($greeting));
            } catch (Throwable $e) {
                // Queda pendiente para el siguiente intento
                Log::warning('No se pudo enviar una tarjeta de cumpleaños', ['greeting' => $greeting->id, 'error' => $e->getMessage()]);

                break;
            }

            $greeting->update(['sent_at' => now()]);
            $sent++;
        }

        return $sent;
    }
}
