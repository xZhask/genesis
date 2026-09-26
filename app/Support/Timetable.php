<?php

namespace App\Support;

use App\Models\ScheduleBlock;
use App\Models\ScheduleSlot;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Horario semanal listo para mostrar: filas por franja y columnas por día.
 * - De una sección: sus franjas (con descansos) y el docente de cada materia.
 * - De un docente: sus clases de todas las secciones, agrupadas por hora.
 */
class Timetable
{
    /**
     * @param  list<array{start: string, end: string, range: string, break: ?string, cells: array<int, list<array{subject: string, subject_id: int, detail: ?string}>>}>  $rows
     */
    public function __construct(public readonly array $rows) {}

    /** @return array<int, string> [1 => 'Lunes', …] según los días con clase */
    public static function days(): array
    {
        $names = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

        return array_intersect_key($names, array_flip(config('school.schedule.days')));
    }

    public static function forSection(Section $section): self
    {
        $section->loadMissing('grade');
        $blocks = ScheduleBlock::where('school_year_id', $section->school_year_id)
            ->where('level', $section->grade->level)
            ->ordered()
            ->get();
        $slots = $section->scheduleSlots()->with('subject')->get()->groupBy('schedule_block_id');
        $teachers = TeacherAssignment::where('section_id', $section->id)->with('teacher')->get()
            ->mapWithKeys(fn ($a) => [$a->subject_id => $a->teacher->name]);

        // El docente sale de las asignaciones: si cambia, el horario lo refleja
        $rows = $blocks->map(function (ScheduleBlock $block) use ($slots, $teachers) {
            $cells = [];
            foreach (($slots[$block->id] ?? collect()) as $slot) {
                $cells[$slot->weekday] = [['subject' => $slot->subject->name, 'subject_id' => $slot->subject_id, 'detail' => $teachers[$slot->subject_id] ?? null]];
            }

            return [
                'start' => $block->start(), 'end' => $block->end(), 'range' => $block->range(),
                'break' => $block->is_break ? ($block->label ?: 'Descanso') : null,
                'cells' => $cells,
            ];
        })->all();

        return new self($rows);
    }

    public static function forTeacher(User $teacher, SchoolYear $year): self
    {
        $pairs = $teacher->assignmentsIn($year)->map(fn ($a) => $a->section_id.'|'.$a->subject_id)->flip();

        $slots = ScheduleSlot::whereIn('section_id', $teacher->assignmentsIn($year)->pluck('section_id')->unique())
            ->with(['block', 'subject', 'section.grade'])
            ->get()
            ->filter(fn (ScheduleSlot $s) => $pairs->has($s->section_id.'|'.$s->subject_id) && ! $s->block->is_break);

        $rows = $slots
            ->groupBy(fn (ScheduleSlot $s) => $s->block->start().'|'.$s->block->end())
            ->sortKeys()
            ->map(function ($group, $key) {
                [$start, $end] = explode('|', $key);
                $cells = [];
                foreach ($group->sortBy(fn ($s) => $s->section->grade->position) as $slot) {
                    $cells[$slot->weekday][] = ['subject' => $slot->subject->name, 'subject_id' => $slot->subject_id, 'detail' => $slot->section->label()];
                }

                return ['start' => $start, 'end' => $end, 'range' => ScheduleBlock::formatRange($start, $end), 'break' => null, 'cells' => $cells];
            })
            ->values()
            ->all();

        return new self($rows);
    }

    /** ¿Hay al menos una clase? */
    public function isEmpty(): bool
    {
        foreach ($this->rows as $row) {
            if ($row['cells']) {
                return false;
            }
        }

        return true;
    }

    /** Día que se muestra primero en el celular: hoy o, si no hay clase hoy, el lunes. */
    public static function today(): int
    {
        $day = now()->dayOfWeekIso;

        return array_key_exists($day, self::days()) ? $day : array_key_first(self::days());
    }

    /** ¿Esta franja se está dictando ahora mismo (hoy, a esta hora)? */
    public function isNow(array $row, int $weekday): bool
    {
        $now = Carbon::now();

        return $weekday === $now->dayOfWeekIso && $row['start'] <= $now->format('H:i') && $now->format('H:i') < $row['end'];
    }
}
