<?php

namespace App\Http\Controllers\Admin\Academic;

use App\Enums\Performance;
use App\Http\Controllers\Controller;
use App\Models\GradingScale;
use App\Models\SchoolYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Escala de valoración del año (SIEE): rangos, desempeños, frases de los logros y pesos. */
class GradingScaleController extends Controller
{
    public function edit(SchoolYear $year): View
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.years.scale', [
            'year' => $year,
            'scale' => GradingScale::forYear($year),
            'performances' => Performance::cases(),
        ]);
    }

    public function update(Request $request, SchoolYear $year): RedirectResponse
    {
        Gate::authorize('manage-academic');

        // Coma decimal, como se escribe en Colombia
        $decimalFields = ['min_score', 'max_score', 'passing_score', 'basic_from', 'high_from', 'superior_from', 'weight_knowing', 'weight_doing', 'weight_being'];
        $request->merge(collect($decimalFields)->mapWithKeys(fn ($f) => [$f => str_replace(',', '.', (string) $request->input($f))])->all());

        $data = $request->validate([
            'min_score' => ['required', 'numeric', 'between:0,10'],
            'max_score' => ['required', 'numeric', 'gt:min_score', 'max:100'],
            'passing_score' => ['required', 'numeric', 'gt:min_score', 'lte:max_score'],
            'decimals' => ['required', 'integer', 'in:1,2'],
            'basic_from' => ['required', 'numeric', 'gt:min_score'],
            'high_from' => ['required', 'numeric', 'gt:basic_from'],
            'superior_from' => ['required', 'numeric', 'gt:high_from', 'lte:max_score'],
            'phrases' => ['required', 'array'],
            'phrases.*' => ['required', 'string', 'max:80'],
            'weight_knowing' => ['required', 'numeric', 'between:0,100'],
            'weight_doing' => ['required', 'numeric', 'between:0,100'],
            'weight_being' => ['required', 'numeric', 'between:0,100'],
        ], [
            'high_from.gt' => 'Alto debe empezar por encima de Básico.',
            'superior_from.gt' => 'Superior debe empezar por encima de Alto.',
            'superior_from.lte' => 'Superior no puede empezar por encima de la nota máxima.',
            'basic_from.gt' => 'Básico debe empezar por encima de la nota mínima.',
            'phrases.*.required' => 'Escribe la frase de cada desempeño.',
        ]);

        $weights = round($data['weight_knowing'] + $data['weight_doing'] + $data['weight_being'], 2);
        if (abs($weights - 100) > 0.01) {
            return back()->withErrors(['weights' => "Los pesos de saber, hacer y ser deben sumar 100 % (ahora suman {$weights} %)."])->withInput();
        }

        $data['phrases'] = collect(Performance::cases())->mapWithKeys(fn ($p) => [$p->value => trim($data['phrases'][$p->value] ?? '')])->all();
        GradingScale::forYear($year)->update($data);

        return back()->with('status_message', "Se guardó la escala de valoración de {$year->year}.");
    }
}
