<?php

namespace App\Support;

use App\Enums\EnrollmentStatus;
use App\Enums\FamilyNotice;
use App\Mail\Family\ContactRequestReviewed;
use App\Mail\Family\EmailChangedNotice;
use App\Mail\Family\EventReminder;
use App\Mail\Family\NewCircular;
use App\Mail\Family\ReportCardsAvailable;
use App\Models\ContactUpdateRequest;
use App\Models\Event;
use App\Models\Guardian;
use App\Models\Period;
use App\Models\Resource;
use App\Models\SchoolYear;
use App\Models\SentNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Correos a las familias. Cada aviso se anota una sola vez en la bandeja
 * de salida (sent_notifications) y un comando programado los envía en tandas
 * sin pasar el tope diario. Así nadie recibe el mismo correo dos veces,
 * aunque el cron se repita o un periodo se cierre, se reabra y se cierre otra vez.
 */
class FamilyMail
{
    /**
     * Anota el aviso para cada acudiente que puede recibirlo: con cuenta
     * activa, con correo y sin haber desactivado los avisos (salvo los que se envían siempre).
     *
     * @param  iterable<Guardian>|Guardian  $guardians
     * @return int avisos nuevos en la bandeja
     */
    public static function queue(FamilyNotice $type, iterable|Guardian $guardians, Model $subject, ?string $email = null): int
    {
        $rows = collect($guardians instanceof Guardian ? [$guardians] : $guardians)
            ->filter(fn (Guardian $g) => self::canReceive($g, $type))
            ->map(fn (Guardian $g) => [
                'type' => $type->value,
                'guardian_id' => $g->id,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'email' => $email,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values();

        // El índice único descarta los que ya estaban
        return $rows->chunk(500)->sum(fn ($chunk) => SentNotification::insertOrIgnore($chunk->all()));
    }

    public static function canReceive(Guardian $guardian, FamilyNotice $type): bool
    {
        $guardian->loadMissing('user');

        return $guardian->user?->is_active
            && self::address($guardian) !== null
            // Sin valor (registro recién creado) vale lo de la base: sí recibe
            && ($type->ignoresOptOut() || $guardian->email_notifications !== false);
    }

    /** El correo de contacto del acudiente o, si no tiene, el de su cuenta. */
    public static function address(Guardian $guardian): ?string
    {
        return $guardian->email ?: $guardian->user?->email ?: null;
    }

    /**
     * Acudientes de estudiantes con matrícula activa en el año, opcionalmente
     * solo de ciertos niveles o grados.
     *
     * @param  list<string>|null  $levels  claves de nivel (preschool, primary…)
     * @param  list<string>|null  $grades  nombres de grado («5.°»)
     * @return Collection<int, Guardian>
     */
    public static function guardiansOf(SchoolYear $year, ?array $levels = null, ?array $grades = null): Collection
    {
        return Guardian::query()
            ->whereHas('students.enrollments', function (Builder $q) use ($year, $levels, $grades) {
                $q->where('school_year_id', $year->id)
                    ->where('status', EnrollmentStatus::Active)
                    ->whereHas('section.grade', function (Builder $g) use ($levels, $grades) {
                        $g->when($levels, fn ($q) => $q->whereIn('level', $levels))
                            ->when($grades, fn ($q) => $q->whereIn('name', $grades));
                    });
            })
            ->with('user')
            ->get();
    }

    /** Recordatorio de un evento para las familias de su nivel (o de todo el colegio). */
    public static function queueEventReminder(Event $event): int
    {
        $year = SchoolYear::current();

        $grades = $event->isForFamiliesOnly() && $event->audience ? $event->audience : null;

        return $year ? self::queue(FamilyNotice::EventReminder, self::guardiansOf($year, $event->level ? [$event->level] : null, $grades), $event) : 0;
    }

    /** «El boletín ya está disponible» al cerrar un periodo. */
    public static function queueReportCards(Period $period): int
    {
        return self::queue(FamilyNotice::ReportCards, self::guardiansOf($period->schoolYear), $period);
    }

    /** Circular nueva para las familias a las que va dirigida. */
    public static function queueCircular(Resource $circular): int
    {
        $year = SchoolYear::current();
        if (! $year || ! $circular->isPublished()) {
            return 0;
        }
        $grades = $circular->isForFamiliesOnly() && $circular->audience ? $circular->audience : null;

        return self::queue(FamilyNotice::NewCircular, self::guardiansOf($year, grades: $grades), $circular);
    }

    /** Resultado de un cambio de contacto; si cambió el correo, también un aviso al anterior. */
    public static function queueContactReview(ContactUpdateRequest $request, ?string $previousEmail = null): void
    {
        self::queue(FamilyNotice::ContactReviewed, $request->guardian, $request);

        if ($previousEmail && strcasecmp($previousEmail, (string) self::address($request->guardian)) !== 0) {
            self::queue(FamilyNotice::EmailChanged, $request->guardian, $request, $previousEmail);
        }
    }

    /** Correos que aún caben hoy. */
    public static function remainingToday(): int
    {
        $sent = SentNotification::where('sent_at', '>=', today())->count();

        return max(0, (int) config('school.family_mail.daily_limit') - $sent);
    }

    /**
     * Envía la siguiente tanda de la bandeja de salida.
     *
     * @return int correos enviados
     */
    public static function deliver(?int $batch = null): int
    {
        $limit = min($batch ?? (int) config('school.family_mail.per_minute'), self::remainingToday());
        if ($limit === 0) {
            return 0;
        }

        $sent = 0;
        $pending = SentNotification::whereNull('sent_at')->orderBy('id')->limit($limit)->with(['guardian.user', 'subject'])->get();

        foreach ($pending as $notice) {
            $mail = $notice->guardian && $notice->subject ? self::mailable($notice) : null;
            $to = $notice->email ?: ($notice->guardian ? self::address($notice->guardian) : null);

            // Si el evento o la circular se borró, o el acudiente ya no puede recibirlo, se descarta
            if (! $mail || ! $to || (! $notice->type->ignoresOptOut() && ! self::canReceive($notice->guardian, $notice->type))) {
                $notice->delete();

                continue;
            }

            try {
                Mail::to($to)->send($mail);
            } catch (Throwable $e) {
                // Queda en la bandeja para el siguiente intento
                Log::warning('No se pudo enviar un correo a una familia', ['notice' => $notice->id, 'error' => $e->getMessage()]);

                break;
            }

            $notice->update(['sent_at' => now()]);
            $sent++;
        }

        return $sent;
    }

    private static function mailable(SentNotification $notice): ?Mailable
    {
        $guardian = $notice->guardian;
        $subject = $notice->subject;

        return match ($notice->type) {
            FamilyNotice::EventReminder => new EventReminder($guardian, $subject),
            FamilyNotice::ReportCards => new ReportCardsAvailable($guardian, $subject),
            FamilyNotice::NewCircular => $subject->isPublished() ? new NewCircular($guardian, $subject) : null,
            FamilyNotice::ContactReviewed => new ContactRequestReviewed($guardian, $subject),
            FamilyNotice::EmailChanged => new EmailChangedNotice($guardian, $subject),
        };
    }
}
