<?php

namespace App\Http\Controllers\Admin\Academic;

use App\Http\Controllers\Controller;
use App\Models\Period;
use App\Models\SchoolYear;
use App\Support\FamilyMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class PeriodController extends Controller
{
    /** Fechas y pesos de todos los periodos del año, en un solo formulario. */
    public function update(Request $request, SchoolYear $year): RedirectResponse
    {
        Gate::authorize('manage-academic');

        $periods = $year->periods;

        $data = $request->validate([
            'periods' => ['required', 'array', 'size:'.$periods->count()],
            'periods.*.starts_on' => ['required', 'date'],
            'periods.*.ends_on' => ['required', 'date'],
            'periods.*.weight' => ['required', 'numeric', 'min:1', 'max:100'],
        ], [
            'periods.*.*.required' => 'Completa las fechas y el peso de cada periodo.',
        ]);

        validator($data)->after(function (Validator $v) use ($data, $year) {
            $total = round(collect($data['periods'])->sum(fn ($p) => (float) $p['weight']), 2);
            if ($total !== 100.0) {
                $v->errors()->add('periods', "Los pesos deben sumar 100 % (ahora suman {$total} %).");
            }

            $previousEnd = null;
            foreach (array_values($data['periods']) as $i => $p) {
                $start = Carbon::parse($p['starts_on']);
                $end = Carbon::parse($p['ends_on']);
                $n = $i + 1;

                if ($end->lt($start)) {
                    $v->errors()->add('periods', "El periodo {$n} termina antes de empezar.");
                }
                if ($previousEnd && $start->lte($previousEnd)) {
                    $v->errors()->add('periods', "El periodo {$n} debe empezar después de que termine el anterior.");
                }
                if ($start->lt($year->starts_on) || $end->gt($year->ends_on)) {
                    $v->errors()->add('periods', "El periodo {$n} se sale de las fechas del año lectivo.");
                }
                $previousEnd = $end;
            }
        })->validate();

        foreach ($periods->values() as $i => $period) {
            $period->update(array_values($data['periods'])[$i]);
        }

        return back()->with('status_message', 'Se guardaron las fechas y los pesos de los periodos.');
    }

    public function close(Request $request, Period $period): RedirectResponse
    {
        Gate::authorize('manage-academic');

        $period->close($request->user());

        // Si se cierra, se reabre y se vuelve a cerrar, la bandeja no repite el aviso
        $notified = $request->boolean('notify') ? FamilyMail::queueReportCards($period) : 0;

        return back()->with('status_message', "{$period->name()} quedó cerrado: sus notas y asistencia ya no se pueden modificar."
            .($notified ? " Se avisará por correo a {$notified} ".($notified === 1 ? 'familia' : 'familias').'.' : ''));
    }

    public function reopen(Request $request, Period $period): RedirectResponse
    {
        Gate::authorize('manage-academic');

        $period->reopen($request->user());

        return back()->with('status_message', "{$period->name()} se reabrió. Queda registrado en el historial.");
    }
}
