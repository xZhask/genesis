<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Period;
use App\Models\SchoolYear;
use App\Support\AcademicAlerts;
use App\Support\Indicators;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** «Resumen» del docente: asistencia del mes y cómo van sus clases en el periodo. */
class SummaryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $year = SchoolYear::current();
        $classes = $year ? $user->assignmentsIn($year) : collect();

        if (! $year || $classes->isEmpty()) {
            return view('portal.teacher.summary', ['year' => $year, 'classes' => $classes]);
        }

        $month = Indicators::month($year, $request->query('mes'));
        $days = Indicators::attendanceByDay($classes, $month);
        $period = $year->periodFor(today()) ?? $year->periods->filter(fn (Period $p) => $p->starts_on->lte(today()))->last();
        $alerts = AcademicAlerts::forTeacher($user, $year);

        return view('portal.teacher.summary', [
            'year' => $year,
            'classes' => $classes,
            'months' => Indicators::months($year),
            'month' => $month,
            'days' => $days,
            'monthTotal' => Indicators::total($days),
            'period' => $period,
            'passFail' => $period ? Indicators::passFailByClass($classes, $period) : collect(),
            'lowStudents' => $alerts->lowPerformance()->pluck('enrollment.id')->unique()->count(),
            'absentStudents' => $alerts->absences()->pluck('enrollment.id')->unique()->count(),
        ]);
    }
}
