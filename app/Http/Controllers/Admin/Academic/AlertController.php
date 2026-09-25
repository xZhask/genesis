<?php

namespace App\Http\Controllers\Admin\Academic;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Support\AcademicAlerts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Alertas del año actual: resumen por sección y detalle de una o de todas. */
class AlertController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-academic');

        $year = SchoolYear::current();
        if (! $year) {
            return view('admin.academic.alerts', ['year' => null]);
        }

        $sections = $year->sections()->with(['grade', 'homeroomTeacher'])->get()
            ->sortBy(fn (Section $s) => [$s->grade->position, $s->name])->values();
        $all = AcademicAlerts::forSections($year, $sections);
        $section = $sections->firstWhere('id', (int) $request->query('seccion'));

        // Estudiantes distintos con alerta, por sección
        $count = fn ($rows) => $rows->groupBy(fn ($r) => $r['enrollment']->section_id)->map(fn ($g) => $g->pluck('enrollment.id')->unique()->count());

        return view('admin.academic.alerts', [
            'year' => $year,
            'sections' => $sections,
            'section' => $section,
            'low' => $count($all->lowPerformance()),
            'absent' => $count($all->absences()),
            'alerts' => $section ? AcademicAlerts::forSections($year, collect([$section])) : $all,
        ]);
    }
}
