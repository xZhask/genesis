<?php

namespace App\Http\Controllers\Admin\Academic;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\SchoolYear;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-academic');

        $years = SchoolYear::orderByDesc('year')->get();
        $year = $years->firstWhere('year', (int) $request->query('lectivo')) ?? $years->firstWhere('is_current', true) ?? $years->first();

        return view('admin.academic.sections.index', [
            'years' => $years,
            'year' => $year,
            'grades' => Grade::ordered()->load(['sections' => fn ($q) => $q->where('school_year_id', $year?->id)]),
            'levels' => collect(config('school.levels'))->keyBy('key'),
        ]);
    }

    public function store(Request $request, SchoolYear $year): RedirectResponse
    {
        Gate::authorize('manage-academic');

        $data = $request->validate([
            'grade_id' => ['required', 'exists:grades,id'],
            'name' => [
                'required', 'string', 'max:10', 'regex:/^[\pL\d]+$/u',
                Rule::unique('sections')->where(fn ($q) => $q->where('grade_id', $request->input('grade_id'))->where('school_year_id', $year->id)),
            ],
        ], [
            'name.required' => 'Escribe el nombre de la sección (por ejemplo, 1 o A).',
            'name.regex' => 'Usa solo letras o números, sin espacios (por ejemplo, 1 o A).',
            'name.unique' => 'Ese grado ya tiene una sección con ese nombre.',
        ]);

        $section = $year->sections()->create($data);

        return back()->with('status_message', "Se creó la sección {$section->label()}.");
    }

    /** Una sección "1" en cada grado que aún no tenga, para empezar rápido. */
    public function createDefaults(SchoolYear $year): RedirectResponse
    {
        Gate::authorize('manage-academic');

        $created = 0;
        foreach (Grade::ordered() as $grade) {
            if (! $grade->sections()->where('school_year_id', $year->id)->exists()) {
                $year->sections()->create(['grade_id' => $grade->id, 'name' => '1']);
                $created++;
            }
        }

        return back()->with('status_message', "Se crearon {$created} secciones.");
    }

    public function destroy(Section $section): RedirectResponse
    {
        Gate::authorize('manage-academic');

        $label = $section->label();
        if ($count = $section->enrollments()->count()) {
            return back()->withErrors(['section' => "No se puede eliminar {$label}: tiene {$count} estudiantes matriculados. Cámbialos de sección primero."]);
        }
        $section->delete();

        return back()->with('status_message', "Se eliminó la sección {$label}.");
    }
}
