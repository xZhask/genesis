<?php

namespace App\Http\Controllers\Admin\Academic;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Support\Indicators;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Indicadores del año: asistencia del mes por sección y aprobación por grado y periodo. */
class IndicatorController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-academic');

        $year = SchoolYear::current();
        if (! $year) {
            return view('admin.academic.indicators', ['year' => null]);
        }

        $month = Indicators::month($year, $request->query('mes'));
        $sections = Indicators::attendanceBySection($year, $month);

        return view('admin.academic.indicators', [
            'year' => $year,
            'months' => Indicators::months($year),
            'month' => $month,
            'sections' => $sections,
            'monthTotal' => Indicators::total($sections),
            'approval' => Indicators::approvalByGrade($year),
        ]);
    }
}
