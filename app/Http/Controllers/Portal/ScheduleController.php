<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Support\Timetable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Horario de clases en el portal: el del docente, el de su grupo o el de un estudiante. */
class ScheduleController extends Controller
{
    public function teacher(Request $request): View
    {
        $user = $request->user();
        $year = SchoolYear::current();
        $homerooms = $year
            ? $user->homeroomSections()->where('school_year_id', $year->id)->with('grade')->get()->sortBy(fn ($s) => [$s->grade->position, $s->name])->values()
            : collect();

        // El director de grupo también consulta el horario de su grupo
        if ($request->filled('seccion')) {
            $section = $homerooms->firstWhere('id', (int) $request->query('seccion'));
            abort_unless($section, 403);

            return $this->show("Horario de {$section->label()}", 'Director de grupo', Timetable::forSection($section), $homerooms, $section);
        }

        return $this->show('Mi horario', $year ? "Año lectivo {$year->year}" : null, $year ? Timetable::forTeacher($user, $year) : new Timetable([]), $homerooms);
    }

    public function guardian(Request $request, Student $student): View
    {
        abort_unless($request->user()->guardian, 403);
        $this->authorize('view', $student);

        return $this->forStudent($student, back: route('portal.guardian.student', $student));
    }

    public function student(Request $request): View
    {
        $student = $request->user()->student;
        abort_unless($student, 403);

        return $this->forStudent($student, title: 'Mi horario');
    }

    private function forStudent(Student $student, ?string $back = null, ?string $title = null): View
    {
        $year = SchoolYear::current();
        $section = $year ? $student->enrollmentFor($year)?->section : null;

        return $this->show(
            $title ?? "Horario de {$student->first_names}",
            $section ? $section->label().' · Año lectivo '.$year->year : 'Sin matrícula en el año actual',
            $section ? Timetable::forSection($section) : new Timetable([]),
            back: $back,
        );
    }

    private function show(string $title, ?string $subtitle, Timetable $timetable, $homerooms = null, ?Section $section = null, ?string $back = null): View
    {
        return view('portal.schedule', [
            'title' => $title,
            'subtitle' => $subtitle,
            'timetable' => $timetable,
            'homerooms' => $homerooms ?? collect(),
            'section' => $section,
            'back' => $back,
            'days' => Timetable::days(),
            'today' => Timetable::today(),
        ]);
    }
}
