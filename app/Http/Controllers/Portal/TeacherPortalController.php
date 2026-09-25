<?php

namespace App\Http\Controllers\Portal;

use App\Actions\RecordAttendance;
use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Exceptions\PeriodClosedException;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SchoolYear;
use App\Models\TeacherAssignment;
use App\Support\AcademicAlerts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Portal docente: sus clases del año actual y la asistencia por materia. */
class TeacherPortalController extends Controller
{
    /** Cookie con la última clase elegida (se recuerda la selección). */
    private const LAST_CLASS = 'asistencia_clase';

    public function home(Request $request): View
    {
        $user = $request->user();
        $year = SchoolYear::current();
        $assignments = $user->assignmentsIn($year);

        // Clases con la asistencia de hoy ya tomada
        $taken = Attendance::whereDate('date', today())
            ->whereIn('subject_id', $assignments->pluck('subject_id'))
            ->whereHas('enrollment', fn ($q) => $q->whereIn('section_id', $assignments->pluck('section_id')))
            ->with('enrollment:id,section_id')
            ->get()
            ->map(fn ($a) => $a->enrollment->section_id.'|'.$a->subject_id)
            ->unique()
            ->all();

        return view('portal.teacher.home', [
            'year' => $year,
            'assignments' => $assignments,
            'taken' => $taken,
            'homerooms' => $year ? $user->homeroomSections()->where('school_year_id', $year->id)->with('grade')->withCount(['enrollments' => fn ($q) => $q->where('status', EnrollmentStatus::Active)])->get() : collect(),
            'period' => $year?->periodFor(today()),
            'alerts' => $year && $assignments->isNotEmpty() ? AcademicAlerts::forTeacher($user, $year) : null,
        ]);
    }

    public function attendance(Request $request): View|Response
    {
        $user = $request->user();
        $year = SchoolYear::current();
        $assignments = $user->assignmentsIn($year);

        if ($assignments->isEmpty()) {
            return view('portal.teacher.attendance', ['assignments' => $assignments, 'year' => $year]);
        }

        $assignment = $assignments->firstWhere('id', (int) ($request->query('clase') ?? $request->cookie(self::LAST_CLASS)))
            ?? $assignments->first();
        $this->authorize('record', $assignment);

        $date = $this->date($request->query('fecha'), $year);
        $period = $year->periodFor($date);

        $enrollments = $assignment->section->enrollments()
            ->where('status', EnrollmentStatus::Active)
            ->with('student')
            ->get()
            ->sortBy(fn ($e) => $e->student->sortName(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $records = Attendance::where('subject_id', $assignment->subject_id)
            ->whereDate('date', $date)
            ->whereIn('enrollment_id', $enrollments->pluck('id'))
            ->with('recorder')
            ->get()
            ->keyBy('enrollment_id');

        return response()
            ->view('portal.teacher.attendance', [
                'year' => $year,
                'assignments' => $assignments,
                'assignment' => $assignment,
                'date' => $date,
                'period' => $period,
                'enrollments' => $enrollments,
                'records' => $records,
                'statuses' => AttendanceStatus::cases(),
                'lastSaved' => $records->sortByDesc('updated_at')->first(),
            ])
            ->withCookie(cookie()->forever(self::LAST_CLASS, (string) $assignment->id));
    }

    public function saveAttendance(Request $request, RecordAttendance $record): RedirectResponse
    {
        $data = $request->validate([
            'clase' => ['required', 'integer'],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'statuses' => ['required', 'array'],
            'statuses.*' => ['required', Rule::enum(AttendanceStatus::class)],
        ], [
            'fecha.before_or_equal' => 'No se puede registrar asistencia de días que no han pasado.',
            'statuses.required' => 'No hay estudiantes para guardar.',
        ]);

        $assignment = TeacherAssignment::with('section.schoolYear.periods')->findOrFail($data['clase']);
        // Un docente solo escribe en sus propias clases (403 aunque cambie el formulario a mano)
        $this->authorize('record', $assignment);

        $date = Carbon::parse($data['fecha']);
        $back = route('portal.teacher.attendance', ['clase' => $assignment->id, 'fecha' => $date->toDateString()]);

        try {
            $count = $record($assignment, $date, $data['statuses'], $request->user());
        } catch (PeriodClosedException $e) {
            return redirect($back)->withErrors(['period' => $e->getMessage()]);
        } catch (ValidationException $e) {
            return redirect($back)->withErrors($e->errors())->withInput();
        }

        $absent = collect($data['statuses'])->filter(fn ($s) => $s === AttendanceStatus::Absent->value)->count();

        return redirect($back)->with('status_message', "Asistencia guardada: {$assignment->label()}, {$date->longDate()}. {$count} estudiantes, "
            .trans_choice(':count ausente.|:count ausentes.', $absent));
    }

    /** Fecha pedida (o hoy), dentro del año lectivo y nunca en el futuro. */
    private function date(?string $value, SchoolYear $year): Carbon
    {
        try {
            $date = $value ? Carbon::createFromFormat('!Y-m-d', $value) : today();
        } catch (\Throwable) {
            $date = today();
        }

        $max = today()->min($year->ends_on);

        return $date->max($year->starts_on)->min($max)->copy()->startOfDay();
    }
}
