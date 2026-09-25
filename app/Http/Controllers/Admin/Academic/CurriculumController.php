<?php

namespace App\Http\Controllers\Admin\Academic;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Grade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Plan de estudios: qué materias ve cada grado y su intensidad horaria (IH). */
class CurriculumController extends Controller
{
    public function index(): RedirectResponse
    {
        Gate::authorize('manage-academic');

        return redirect()->route('admin.academic.curriculum.edit', Grade::orderBy('position')->value('id'));
    }

    public function edit(Grade $grade): View
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.curriculum.edit', [
            'grade' => $grade,
            'grades' => Grade::ordered(),
            'areas' => Area::with('subjects')->orderBy('position')->orderBy('name')->get(),
            'plan' => $grade->subjects()->pluck('grade_subject.weekly_hours', 'subjects.id'),
        ]);
    }

    public function update(Request $request, Grade $grade): RedirectResponse
    {
        Gate::authorize('manage-academic');

        $data = $request->validate([
            'subjects' => ['array'],
            'subjects.*.included' => ['boolean'],
            'subjects.*.hours' => ['nullable', 'integer', 'between:0,20'],
        ], ['subjects.*.hours.between' => 'La intensidad horaria debe estar entre 0 y 20 horas.']);

        $plan = collect($data['subjects'] ?? [])
            ->filter(fn ($row) => ! empty($row['included']))
            ->mapWithKeys(fn ($row, $subjectId) => [$subjectId => ['weekly_hours' => (int) ($row['hours'] ?? 0)]]);

        $grade->subjects()->sync($plan);

        return back()->with('status_message', "Se guardó el plan de estudios de {$grade->name}: {$plan->count()} materias, {$plan->sum('weekly_hours')} horas semanales.");
    }

    public function copy(Request $request, Grade $grade): RedirectResponse
    {
        Gate::authorize('manage-academic');

        $source = Grade::findOrFail($request->validate(['from' => ['required', 'exists:grades,id']])['from']);

        $grade->subjects()->sync($source->subjects->mapWithKeys(fn ($s) => [$s->id => ['weekly_hours' => $s->pivot->weekly_hours]]));

        return back()->with('status_message', "Se copió a {$grade->name} el plan de estudios de {$source->name}.");
    }
}
