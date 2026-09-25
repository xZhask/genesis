<?php

namespace App\Http\Controllers\Admin\Academic;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Áreas y materias del plan de estudios. */
class SubjectController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.subjects.index', [
            'areas' => Area::with(['subjects' => fn ($q) => $q->withCount('grades')])->orderBy('position')->orderBy('name')->get(),
        ]);
    }

    public function storeArea(Request $request): RedirectResponse
    {
        Gate::authorize('manage-academic');

        $data = $request->validate(
            ['area_name' => ['required', 'string', 'max:80', 'unique:areas,name']],
            ['area_name.unique' => 'Ya existe un área con ese nombre.'],
            ['area_name' => 'nombre del área'],
        );

        $area = Area::create(['name' => $data['area_name'], 'position' => (int) Area::max('position') + 1]);

        return back()->with('status_message', "Se creó el área {$area->name}.");
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-academic');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:subjects,name'],
            'area_id' => ['required', 'exists:areas,id'],
        ], ['name.unique' => 'Ya existe una materia con ese nombre.'], ['name' => 'nombre de la materia', 'area_id' => 'área']);

        $subject = Subject::create([...$data, 'position' => (int) Subject::where('area_id', $data['area_id'])->max('position') + 1]);

        return back()->with('status_message', "Se creó la materia {$subject->name}. Agrégala al plan de estudios de los grados que la ven.");
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        Gate::authorize('manage-academic');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('subjects', 'name')->ignore($subject)],
            'area_id' => ['required', 'exists:areas,id'],
        ], ['name.unique' => 'Ya existe una materia con ese nombre.'], ['name' => 'nombre de la materia']);

        $subject->update($data);

        return back()->with('status_message', "Se guardó la materia {$subject->name}.");
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        Gate::authorize('manage-academic');

        if ($subject->grades()->exists()) {
            return back()->withErrors(['subject' => "«{$subject->name}» está en el plan de estudios de algún grado. Quítala de esos planes antes de eliminarla."]);
        }

        $subject->delete();

        return back()->with('status_message', "Se eliminó la materia {$subject->name}.");
    }

    public function destroyArea(Area $area): RedirectResponse
    {
        Gate::authorize('manage-academic');

        if ($area->subjects()->exists()) {
            return back()->withErrors(['area' => "El área {$area->name} todavía tiene materias."]);
        }

        $area->delete();

        return back()->with('status_message', "Se eliminó el área {$area->name}.");
    }
}
