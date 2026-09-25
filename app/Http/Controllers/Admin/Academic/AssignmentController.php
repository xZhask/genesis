<?php

namespace App\Http\Controllers\Admin\Academic;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Asignaciones docentes: quién dicta cada materia del plan en cada sección y quién dirige el grupo. */
class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-academic');

        $years = SchoolYear::orderByDesc('year')->get();
        $year = $years->firstWhere('year', (int) $request->query('lectivo')) ?? $years->firstWhere('is_current', true) ?? $years->first();

        $sections = $year
            ? $year->sections()->with(['grade', 'assignments'])->get()->sortBy(fn (Section $s) => [$s->grade->position, $s->name])->values()
            : collect();
        $section = $sections->firstWhere('id', (int) $request->query('seccion')) ?? $sections->first();

        return view('admin.academic.assignments.index', [
            'years' => $years,
            'year' => $year,
            'sections' => $sections,
            'section' => $section,
            'subjects' => $section?->grade->subjects()->with('area')->get() ?? collect(),
            'assigned' => $section?->assignments->pluck('teacher_id', 'subject_id') ?? collect(),
            'teachers' => User::where('role', Role::Teacher)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        Gate::authorize('manage-academic');

        $teacherRule = Rule::exists('users', 'id')->where('role', Role::Teacher->value)->where('is_active', true);
        $planIds = $section->grade->subjects()->pluck('subjects.id')->all();

        $data = $request->validate([
            'homeroom_teacher_id' => ['nullable', $teacherRule],
            'teachers' => ['array'],
            'teachers.*' => ['nullable', $teacherRule],
        ], [
            'homeroom_teacher_id.exists' => 'Elige un docente activo.',
            'teachers.*.exists' => 'Elige un docente activo.',
        ]);

        $teachers = collect($data['teachers'] ?? [])->only($planIds);

        DB::transaction(function () use ($section, $data, $teachers, $planIds) {
            $section->update(['homeroom_teacher_id' => $data['homeroom_teacher_id'] ?? null]);

            foreach ($planIds as $subjectId) {
                $teacherId = $teachers[$subjectId] ?? null;
                if ($teacherId) {
                    TeacherAssignment::updateOrCreate(
                        ['section_id' => $section->id, 'subject_id' => $subjectId],
                        ['teacher_id' => $teacherId],
                    );
                } else {
                    TeacherAssignment::where('section_id', $section->id)->where('subject_id', $subjectId)->delete();
                }
            }

            // Materias que salieron del plan de estudios
            TeacherAssignment::where('section_id', $section->id)->whereNotIn('subject_id', $planIds)->delete();
        });

        return back()->with('status_message', "Se guardaron las asignaciones de {$section->label()}: {$teachers->filter()->count()} de ".count($planIds).' materias con docente.');
    }
}
