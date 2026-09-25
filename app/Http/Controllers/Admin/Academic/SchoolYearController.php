<?php

namespace App\Http\Controllers\Admin\Academic;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SchoolYearController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-academic');

        $latest = SchoolYear::max('year');

        return view('admin.academic.years.index', [
            'years' => SchoolYear::with('periods')->withCount('sections')->orderByDesc('year')->get(),
            'suggestedYear' => $latest ? $latest + 1 : max(2027, (int) now()->year),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-academic');

        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2026,2100', 'unique:school_years,year'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'is_current' => ['boolean'],
        ], [
            'year.unique' => 'Ese año lectivo ya existe.',
            'ends_on.after' => 'La fecha de fin debe ser posterior a la de inicio.',
        ], ['year' => 'año', 'starts_on' => 'inicio de clases', 'ends_on' => 'fin de clases']);

        $year = DB::transaction(function () use ($data) {
            $year = SchoolYear::create($data);
            $year->createPeriods((int) config('school.academic.periods'));

            if (! empty($data['is_current']) || ! SchoolYear::where('is_current', true)->exists()) {
                $year->makeCurrent();
            }

            return $year;
        });

        return redirect()
            ->route('admin.academic.years.edit', $year)
            ->with('status_message', "Se creó el año lectivo {$year->year} con sus ".config('school.academic.periods').' periodos. Revisa sus fechas.');
    }

    public function edit(SchoolYear $year): View
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.years.edit', [
            'year' => $year->load(['periods.statusChanges.user', 'periods.closer']),
        ]);
    }

    public function update(Request $request, SchoolYear $year): RedirectResponse
    {
        Gate::authorize('manage-academic');

        $data = $request->validate([
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
        ], ['ends_on.after' => 'La fecha de fin debe ser posterior a la de inicio.']);

        $year->update($data);

        return back()->with('status_message', "Se guardaron las fechas del año {$year->year}.");
    }

    public function makeCurrent(SchoolYear $year): RedirectResponse
    {
        Gate::authorize('manage-academic');

        $year->makeCurrent();

        return back()->with('status_message', "{$year->year} es ahora el año lectivo actual del portal.");
    }
}
