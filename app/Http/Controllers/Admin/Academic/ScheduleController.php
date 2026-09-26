<?php

namespace App\Http\Controllers\Admin\Academic;

use App\Http\Controllers\Controller;
use App\Models\ScheduleBlock;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Support\ScheduleConflicts;
use App\Support\Timetable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Horario de clases fijo para el año: franjas por nivel (horas de clase y
 * descansos) y la cuadrícula de cada sección (materia por franja y día).
 */
class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-academic');

        $years = SchoolYear::orderByDesc('year')->get();
        $year = $years->firstWhere('year', (int) $request->query('lectivo')) ?? $years->firstWhere('is_current', true) ?? $years->first();
        if (! $year) {
            return view('admin.academic.schedule.index', ['year' => null, 'years' => $years]);
        }

        $blocks = $year->scheduleBlocks()->get()->groupBy('level');
        $sections = $year->sections()->with('grade')->withCount('scheduleSlots')->get()
            ->sortBy(fn (Section $s) => [$s->grade->position, $s->name])->values();
        $days = count(Timetable::days());

        return view('admin.academic.schedule.index', [
            'years' => $years,
            'year' => $year,
            'levels' => config('school.levels'),
            'blocks' => $blocks,
            'sections' => $sections,
            // Casillas de clase de cada nivel (sin descansos)
            'capacity' => $blocks->map(fn ($b) => $b->reject->is_break->count() * $days),
        ]);
    }

    /** Guarda las franjas de un nivel. Borrar una franja borra sus clases. */
    public function updateBlocks(Request $request, SchoolYear $year, string $level): RedirectResponse
    {
        Gate::authorize('manage-academic');
        abort_unless(in_array($level, collect(config('school.levels'))->pluck('key')->all(), true), 404);

        // Las filas nuevas vacías se ignoran
        $rows = collect($request->input('blocks', []))
            ->reject(fn ($row) => blank($row['id'] ?? null) && blank($row['start'] ?? null) && blank($row['end'] ?? null))
            ->values();
        $request->merge(['blocks' => $rows->all()]);

        $data = $request->validate([
            'blocks' => ['array', 'max:20'],
            'blocks.*.id' => ['nullable', 'integer', Rule::exists('schedule_blocks', 'id')->where('school_year_id', $year->id)->where('level', $level)],
            'blocks.*.delete' => ['nullable', 'boolean'],
            'blocks.*.start' => ['exclude_if:blocks.*.delete,1', 'required', 'date_format:H:i'],
            'blocks.*.end' => ['exclude_if:blocks.*.delete,1', 'required', 'date_format:H:i', 'after:blocks.*.start'],
            'blocks.*.is_break' => ['nullable', 'boolean'],
            'blocks.*.label' => ['nullable', 'string', 'max:40'],
        ], [
            'blocks.*.start.required' => 'Escribe la hora de inicio.',
            'blocks.*.end.required' => 'Escribe la hora de fin.',
            'blocks.*.end.after' => 'La hora de fin debe ser posterior a la de inicio.',
            'blocks.*.*.date_format' => 'Usa el formato de hora 07:00.',
        ]);

        $keep = collect($data['blocks'] ?? [])->reject(fn ($row) => ! empty($row['delete']))->sortBy('start')->values();
        foreach ($keep as $i => $row) {
            $next = $keep[$i + 1] ?? null;
            if ($next && $next['start'] < $row['end']) {
                throw ValidationException::withMessages([
                    'blocks' => "Las franjas {$row['start']}–{$row['end']} y {$next['start']}–{$next['end']} se cruzan.",
                ]);
            }
        }

        DB::transaction(function () use ($data, $year, $level) {
            foreach ($data['blocks'] ?? [] as $row) {
                $block = filled($row['id'] ?? null) ? ScheduleBlock::find($row['id']) : null;
                if (! empty($row['delete'])) {
                    $block?->delete();

                    continue;
                }
                $values = [
                    'starts_at' => $row['start'], 'ends_at' => $row['end'],
                    'is_break' => (bool) ($row['is_break'] ?? false),
                    'label' => ($row['is_break'] ?? false) ? (trim((string) ($row['label'] ?? '')) ?: 'Descanso') : null,
                ];
                $block
                    ? $block->update($values)
                    : ScheduleBlock::create($values + ['school_year_id' => $year->id, 'level' => $level]);
            }
            // Una franja que pasa a ser descanso no puede tener clases
            ScheduleBlock::where('school_year_id', $year->id)->where('level', $level)->where('is_break', true)
                ->each(fn (ScheduleBlock $b) => $b->slots()->delete());
        });

        $name = collect(config('school.levels'))->firstWhere('key', $level)['name'];

        return redirect()->route('admin.academic.schedule.index', ['lectivo' => $year->year])
            ->withFragment("franjas-{$level}")
            ->with('status_message', "Se guardaron las franjas de {$name}.");
    }

    public function edit(Request $request, Section $section): View
    {
        Gate::authorize('manage-academic');
        $section->load(['grade.subjects', 'schoolYear']);

        $blocks = $this->blocks($section);
        $grid = [];
        // «Copiar de otra sección» solo llena el formulario; se guarda al revisar
        $source = $request->filled('copiar')
            ? Section::where('school_year_id', $section->school_year_id)->where('grade_id', $section->grade_id)->whereKeyNot($section->id)->find($request->query('copiar'))
            : null;
        foreach (($source ?? $section)->scheduleSlots()->get() as $slot) {
            $grid[$slot->schedule_block_id][$slot->weekday] = $slot->subject_id;
        }

        return view('admin.academic.schedule.edit', [
            'section' => $section,
            'blocks' => $blocks,
            'grid' => $grid,
            'source' => $source,
            'days' => Timetable::days(),
            'teachers' => TeacherAssignment::where('section_id', $section->id)->with('teacher')->get()->mapWithKeys(fn ($a) => [$a->subject_id => $a->teacher->name]),
            'siblings' => Section::where('school_year_id', $section->school_year_id)->where('grade_id', $section->grade_id)->whereKeyNot($section->id)->with('grade')->get(),
            'conflicts' => $source ? [] : ScheduleConflicts::forSection($section),
        ]);
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        Gate::authorize('manage-academic');
        $section->load('grade.subjects');

        $blocks = $this->blocks($section)->reject->is_break->keyBy('id');
        $subjects = $section->grade->subjects->pluck('id')->all();
        $days = array_keys(Timetable::days());

        $request->validate(['slots' => ['array'], 'slots.*' => ['array'], 'slots.*.*' => ['nullable', 'integer', Rule::in($subjects)]], [
            'slots.*.*.in' => 'Una de las materias no es de este grado.',
        ]);

        $grid = [];
        foreach ($request->input('slots', []) as $blockId => $row) {
            foreach ((array) $row as $weekday => $subjectId) {
                if (blank($subjectId)) {
                    continue;
                }
                if (! isset($blocks[$blockId]) || ! in_array((int) $weekday, $days, true)) {
                    throw ValidationException::withMessages(['slots' => 'Las franjas cambiaron: recarga la página.']);
                }
                $grid[(int) $blockId][(int) $weekday] = (int) $subjectId;
            }
        }

        if ($conflicts = ScheduleConflicts::check($section, $grid)) {
            return back()->withInput()->withErrors(['conflicts' => $conflicts]);
        }

        DB::transaction(function () use ($section, $grid) {
            $section->scheduleSlots()->delete();
            foreach ($grid as $blockId => $row) {
                foreach ($row as $weekday => $subjectId) {
                    $section->scheduleSlots()->create(['schedule_block_id' => $blockId, 'weekday' => $weekday, 'subject_id' => $subjectId]);
                }
            }
        });

        return redirect()->route('admin.academic.schedule.edit', $section)
            ->with('status_message', "Se guardó el horario de {$section->label()}.");
    }

    private function blocks(Section $section)
    {
        return ScheduleBlock::where('school_year_id', $section->school_year_id)->where('level', $section->grade->level)->ordered()->get();
    }
}
