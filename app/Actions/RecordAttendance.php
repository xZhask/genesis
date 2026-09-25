<?php

namespace App\Actions;

use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Exceptions\PeriodClosedException;
use App\Models\Attendance;
use App\Models\Period;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Guarda la asistencia de una clase (materia y sección) en una fecha.
 * Es la única vía de escritura: aquí se hace cumplir el cierre de periodos,
 * sin importar desde dónde llegue la petición.
 */
class RecordAttendance
{
    /**
     * @param  array<int|string, string>  $statuses  matrícula => estado
     * @return int registros guardados
     *
     * @throws PeriodClosedException
     * @throws ValidationException
     */
    public function __invoke(TeacherAssignment $assignment, Carbon $date, array $statuses, User $by): int
    {
        $period = static::periodFor($assignment, $date);

        if ($period->isClosed()) {
            throw new PeriodClosedException($period);
        }

        // Solo estudiantes matriculados (activos) en la sección
        $enrollments = $assignment->section->enrollments()->where('status', EnrollmentStatus::Active)->pluck('id')->all();
        $unknown = array_diff(array_keys($statuses), $enrollments);
        if ($unknown) {
            throw ValidationException::withMessages(['statuses' => 'La lista cambió: hay estudiantes que ya no están en esta sección. Recarga la página.']);
        }

        return DB::transaction(function () use ($assignment, $date, $statuses, $by, $period) {
            // Se vuelve a leer el periodo con bloqueo: si lo cierran en este instante, no se escribe
            if (Period::lockForUpdate()->find($period->id)->isClosed()) {
                throw new PeriodClosedException($period);
            }

            foreach ($statuses as $enrollmentId => $status) {
                Attendance::updateOrCreate(
                    ['enrollment_id' => $enrollmentId, 'subject_id' => $assignment->subject_id, 'date' => $date->toDateString()],
                    ['period_id' => $period->id, 'status' => AttendanceStatus::from($status), 'recorded_by' => $by->id],
                );
            }

            return count($statuses);
        });
    }

    /** Periodo del año de la sección que contiene la fecha. */
    public static function periodFor(TeacherAssignment $assignment, Carbon $date): Period
    {
        $year = $assignment->section->schoolYear;
        $period = $year->periodFor($date);

        if (! $period) {
            throw ValidationException::withMessages([
                'date' => "La fecha no está dentro de los periodos del año lectivo {$year->year}.",
            ]);
        }

        return $period;
    }
}
