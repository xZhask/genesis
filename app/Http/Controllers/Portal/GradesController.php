<?php

namespace App\Http\Controllers\Portal;

use App\Enums\EnrollmentStatus;
use App\Enums\EvaluationComponent;
use App\Enums\Performance;
use App\Exceptions\PeriodClosedException;
use App\Http\Controllers\Controller;
use App\Models\GradeItem;
use App\Models\GradingScale;
use App\Models\Period;
use App\Models\PeriodObjective;
use App\Models\SchoolYear;
use App\Models\Score;
use App\Models\TeacherAssignment;
use App\Support\ClassGrades;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Planilla de notas del docente: actividades por componente (saber, hacer,
 * ser), notas de cada estudiante y los tres logros del periodo. Un periodo
 * cerrado es de solo lectura: el servidor rechaza cualquier escritura.
 */
class GradesController extends Controller
{
    private const LAST_CLASS = 'notas_clase';

    public function index(Request $request): View|Response
    {
        $user = $request->user();
        $year = SchoolYear::current();
        $assignments = $user->assignmentsIn($year);

        if ($assignments->isEmpty()) {
            return view('portal.teacher.grades', ['assignments' => $assignments, 'year' => $year]);
        }

        $assignment = $assignments->firstWhere('id', (int) ($request->query('clase') ?? $request->cookie(self::LAST_CLASS)))
            ?? $assignments->first();
        $this->authorize('record', $assignment);

        $periods = $year->periods;
        $period = $periods->firstWhere('id', (int) $request->query('periodo'))
            ?? $year->periodFor(today())
            ?? $periods->last(fn (Period $p) => $p->starts_on->lte(today()))
            ?? $periods->first();

        $data = ['year' => $year, 'assignments' => $assignments, 'assignment' => $assignment, 'periods' => $periods, 'period' => $period];

        if ($assignment->section->grade->isPreschool()) {
            return response()->view('portal.teacher.grades', $data + ['preschool' => true])
                ->withCookie(cookie()->forever(self::LAST_CLASS, (string) $assignment->id));
        }

        $scale = GradingScale::forYear($year);
        $grades = new ClassGrades($assignment->section, $assignment->subject, $period, $scale);
        $enrollments = $assignment->section->enrollments()
            ->where('status', EnrollmentStatus::Active)
            ->with('student')
            ->get()
            ->sortBy(fn ($e) => $e->student->sortName(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->view('portal.teacher.grades', $data + [
            'preschool' => false,
            'scale' => $scale,
            'grades' => $grades,
            'enrollments' => $enrollments,
            'objectives' => PeriodObjective::where('section_id', $assignment->section_id)
                ->where('subject_id', $assignment->subject_id)
                ->where('period_id', $period->id)
                ->pluck('text', 'component'),
            'components' => EvaluationComponent::cases(),
            'performances' => Performance::cases(),
        ])->withCookie(cookie()->forever(self::LAST_CLASS, (string) $assignment->id));
    }

    public function storeItem(Request $request): RedirectResponse
    {
        [$assignment, $period] = $this->context($request);

        $data = $request->validate([
            'component' => ['required', Rule::enum(EvaluationComponent::class)],
            'name' => ['required', 'string', 'max:80'],
        ], [
            'name.required' => 'Escribe el nombre de la actividad (por ejemplo, «Taller 1» o «Evaluación escrita»).',
        ]);

        return $this->write($assignment, $period, function () use ($assignment, $period, $data, $request) {
            GradeItem::create([
                'section_id' => $assignment->section_id,
                'subject_id' => $assignment->subject_id,
                'period_id' => $period->id,
                'component' => $data['component'],
                'name' => $data['name'],
                'position' => (int) GradeItem::where('section_id', $assignment->section_id)->where('subject_id', $assignment->subject_id)
                    ->where('period_id', $period->id)->max('position') + 1,
                'created_by' => $request->user()->id,
            ]);

            return "Se agregó la actividad «{$data['name']}».";
        });
    }

    public function destroyItem(Request $request, GradeItem $item): RedirectResponse
    {
        $assignment = TeacherAssignment::where('section_id', $item->section_id)
            ->where('subject_id', $item->subject_id)
            ->where('teacher_id', $request->user()->id)
            ->first();
        abort_unless($assignment, 403);
        $this->authorize('record', $assignment);

        return $this->write($assignment, $item->period, function () use ($item) {
            $item->delete();

            return "Se eliminó la actividad «{$item->name}» con sus notas.";
        });
    }

    public function saveScores(Request $request): RedirectResponse
    {
        [$assignment, $period] = $this->context($request);
        $scale = GradingScale::forYear($period->schoolYear);

        $items = GradeItem::where('section_id', $assignment->section_id)->where('subject_id', $assignment->subject_id)
            ->where('period_id', $period->id)->pluck('id')->all();
        $enrollments = $assignment->section->enrollments()->where('status', EnrollmentStatus::Active)->pluck('id')->all();

        // Validación celda por celda: acepta coma o punto decimal ("4,5")
        $values = [];
        $errors = [];
        foreach ((array) $request->input('scores', []) as $itemId => $row) {
            if (! in_array((int) $itemId, $items, true)) {
                throw ValidationException::withMessages(['scores' => 'La planilla cambió. Recarga la página.']);
            }
            foreach ((array) $row as $enrollmentId => $raw) {
                if (! in_array((int) $enrollmentId, $enrollments, true)) {
                    throw ValidationException::withMessages(['scores' => 'La lista cambió: hay estudiantes que ya no están en esta sección. Recarga la página.']);
                }
                $raw = str_replace(',', '.', trim((string) $raw));
                if ($raw === '') {
                    $values[(int) $itemId][(int) $enrollmentId] = null;

                    continue;
                }
                if (! is_numeric($raw) || (float) $raw < $scale->min_score || (float) $raw > $scale->max_score || ! preg_match('/^\d+(\.\d{1,2})?$/', $raw)) {
                    $errors["scores.{$itemId}.{$enrollmentId}"] = "Escribe una nota entre {$scale->format($scale->min_score)} y {$scale->format($scale->max_score)}.";

                    continue;
                }
                $values[(int) $itemId][(int) $enrollmentId] = (float) $raw;
            }
        }

        if ($errors) {
            return $this->back($assignment, $period)->withErrors($errors + ['scores' => 'Hay notas por corregir (marcadas en rojo). No se guardó nada.'])->withInput();
        }

        return $this->write($assignment, $period, function () use ($values, $request) {
            $saved = 0;
            foreach ($values as $itemId => $row) {
                foreach ($row as $enrollmentId => $value) {
                    if ($value === null) {
                        Score::where('grade_item_id', $itemId)->where('enrollment_id', $enrollmentId)->delete();

                        continue;
                    }
                    Score::updateOrCreate(
                        ['grade_item_id' => $itemId, 'enrollment_id' => $enrollmentId],
                        ['value' => $value, 'recorded_by' => $request->user()->id],
                    );
                    $saved++;
                }
            }

            return "Notas guardadas ({$saved}).";
        });
    }

    public function saveObjectives(Request $request): RedirectResponse
    {
        [$assignment, $period] = $this->context($request);

        $data = $request->validate([
            'objectives' => ['array'],
            'objectives.*' => ['nullable', 'string', 'max:300'],
        ], ['objectives.*.max' => 'Cada logro puede tener hasta 300 caracteres.']);

        return $this->write($assignment, $period, function () use ($assignment, $period, $data, $request) {
            foreach (EvaluationComponent::cases() as $component) {
                $text = trim((string) ($data['objectives'][$component->value] ?? ''));
                $keys = ['section_id' => $assignment->section_id, 'subject_id' => $assignment->subject_id, 'period_id' => $period->id, 'component' => $component->value];

                $text === ''
                    ? PeriodObjective::where($keys)->delete()
                    : PeriodObjective::updateOrCreate($keys, ['text' => $text, 'updated_by' => $request->user()->id]);
            }

            return 'Logros guardados.';
        });
    }

    /**
     * Clase y periodo del formulario: la clase debe ser del docente y el
     * periodo, del año de esa sección.
     *
     * @return array{0: TeacherAssignment, 1: Period}
     */
    private function context(Request $request): array
    {
        $request->validate(['clase' => ['required', 'integer'], 'periodo' => ['required', 'integer']]);

        $assignment = TeacherAssignment::with(['section.grade', 'subject'])->findOrFail($request->input('clase'));
        $this->authorize('record', $assignment);
        abort_if($assignment->section->grade->isPreschool(), 403, 'Preescolar se evalúa de forma descriptiva.');

        $period = Period::where('school_year_id', $assignment->section->school_year_id)->findOrFail($request->input('periodo'));

        return [$assignment, $period];
    }

    /** Ejecuta la escritura solo si el periodo sigue abierto (regla de seguridad 3). */
    private function write(TeacherAssignment $assignment, Period $period, callable $callback): RedirectResponse
    {
        try {
            $message = DB::transaction(function () use ($period, $callback) {
                $period->ensureOpen();

                return $callback();
            });
        } catch (PeriodClosedException $e) {
            return $this->back($assignment, $period)->withErrors(['period' => $e->getMessage()]);
        }

        return $this->back($assignment, $period)->with('status_message', $message);
    }

    private function back(TeacherAssignment $assignment, Period $period): RedirectResponse
    {
        return redirect()->route('portal.teacher.grades', ['clase' => $assignment->id, 'periodo' => $period->id]);
    }
}
