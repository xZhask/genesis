<?php

namespace App\Http\Controllers\Portal;

use App\Enums\EnrollmentStatus;
use App\Exceptions\PeriodClosedException;
use App\Http\Controllers\Controller;
use App\Models\GradingScale;
use App\Models\Period;
use App\Models\PeriodReport;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Support\AcademicAlerts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Director de grupo: comportamiento y observaciones de cada estudiante por periodo. */
class HomeroomController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $year = SchoolYear::current();
        $sections = $year
            ? $user->homeroomSections()->where('school_year_id', $year->id)->with('grade')->get()->sortBy(fn ($s) => [$s->grade->position, $s->name])->values()
            : collect();
        abort_if($sections->isEmpty(), 403, 'No eres director de grupo este año.');

        $section = $sections->firstWhere('id', (int) $request->query('seccion')) ?? $sections->first();
        $period = $year->periods->firstWhere('id', (int) $request->query('periodo'))
            ?? $year->periodFor(today())
            ?? $year->periods->last(fn (Period $p) => $p->starts_on->lte(today()))
            ?? $year->periods->first();

        $enrollments = $section->enrollments()->where('status', EnrollmentStatus::Active)->with('student')->get()
            ->sortBy(fn ($e) => $e->student->sortName(), SORT_NATURAL | SORT_FLAG_CASE)->values();

        return view('portal.teacher.homeroom', [
            'year' => $year,
            'sections' => $sections,
            'section' => $section,
            'period' => $period,
            'enrollments' => $enrollments,
            'reports' => PeriodReport::where('period_id', $period->id)->whereIn('enrollment_id', $enrollments->pluck('id'))->get()->keyBy('enrollment_id'),
            'scale' => GradingScale::forYear($year),
            'alerts' => AcademicAlerts::forSections($year, collect([$section])),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $request->validate(['seccion' => ['required', 'integer'], 'periodo' => ['required', 'integer']]);
        $section = Section::findOrFail($request->input('seccion'));
        // Solo el director de esa sección
        abort_unless($request->user()->is_active && $section->homeroom_teacher_id === $request->user()->id, 403);

        $period = Period::where('school_year_id', $section->school_year_id)->findOrFail($request->input('periodo'));
        $scale = GradingScale::forYear($section->schoolYear);
        $enrollments = $section->enrollments()->where('status', EnrollmentStatus::Active)->pluck('id')->all();

        $request->merge(['behavior' => collect($request->input('behavior', []))->map(fn ($v) => $v === null ? null : str_replace(',', '.', trim($v)))->all()]);
        $data = $request->validate([
            'behavior' => ['array'],
            'behavior.*' => ['nullable', 'numeric', "between:{$scale->min_score},{$scale->max_score}", 'regex:/^\d+(\.\d{1,2})?$/'],
            'observations' => ['array'],
            'observations.*' => ['nullable', 'string', 'max:1000'],
        ], [
            'behavior.*.between' => "Escribe una nota entre {$scale->format($scale->min_score)} y {$scale->format($scale->max_score)}.",
            'behavior.*.numeric' => 'Escribe una nota válida.',
            'behavior.*.regex' => 'Usa máximo dos decimales.',
            'observations.*.max' => 'La observación puede tener hasta 1.000 caracteres.',
        ]);

        $back = route('portal.teacher.homeroom', ['seccion' => $section->id, 'periodo' => $period->id]);
        $ids = array_unique([...array_keys($data['behavior'] ?? []), ...array_keys($data['observations'] ?? [])]);
        if (array_diff($ids, $enrollments)) {
            return redirect($back)->withErrors(['period' => 'La lista cambió: recarga la página.']);
        }

        try {
            DB::transaction(function () use ($period, $ids, $data, $request) {
                $period->ensureOpen();
                foreach ($ids as $id) {
                    $behavior = $data['behavior'][$id] ?? null;
                    $observations = trim((string) ($data['observations'][$id] ?? ''));
                    $keys = ['enrollment_id' => $id, 'period_id' => $period->id];

                    ($behavior === null && $observations === '')
                        ? PeriodReport::where($keys)->delete()
                        : PeriodReport::updateOrCreate($keys, [
                            'behavior' => $behavior,
                            'observations' => $observations ?: null,
                            'recorded_by' => $request->user()->id,
                        ]);
                }
            });
        } catch (PeriodClosedException $e) {
            return redirect($back)->withErrors(['period' => $e->getMessage()]);
        }

        return redirect($back)->with('status_message', "Se guardó el comportamiento de {$section->label()} ({$period->name()}).");
    }
}
