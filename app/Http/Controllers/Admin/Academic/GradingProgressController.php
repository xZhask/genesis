<?php

namespace App\Http\Controllers\Admin\Academic;

use App\Http\Controllers\Controller;
use App\Models\Period;
use App\Support\GradingProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Avance de notas de un periodo: qué falta antes de cerrarlo. */
class GradingProgressController extends Controller
{
    public function show(Request $request, Period $period): View
    {
        Gate::authorize('manage-academic');

        $sections = GradingProgress::forPeriod($period);
        $classes = $sections->flatMap(fn ($s) => $s['classes']);
        $onlyPending = $request->query('ver') !== 'todas';

        return view('admin.academic.years.progress', [
            'period' => $period->load('schoolYear'),
            'year' => $period->schoolYear,
            'sections' => $onlyPending
                ? $sections->map(fn ($s) => ['classes' => $s['classes']->reject(fn ($c) => $c['complete'])] + $s)
                    ->filter(fn ($s) => $s['classes']->isNotEmpty() || $s['behavior'] < $s['students'])
                : $sections,
            'onlyPending' => $onlyPending,
            'total' => $classes->count(),
            'complete' => $classes->where('complete', true)->count(),
            'behaviorMissing' => $sections->sum(fn ($s) => max(0, $s['students'] - $s['behavior'])),
        ]);
    }
}
