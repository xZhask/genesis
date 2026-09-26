<?php

namespace App\Support;

use App\Models\ScheduleBlock;
use App\Models\ScheduleSlot;
use App\Models\Section;
use App\Models\TeacherAssignment;
use Illuminate\Support\Collection;

/**
 * Choques de horario: un docente no puede estar en dos secciones a la
 * misma hora. Compara por hora (no por franja), porque cada nivel tiene sus
 * propias franjas.
 */
class ScheduleConflicts
{
    /**
     * @param  array<int, array<int, int>>  $grid  [franja][día] => materia, de la sección que se revisa
     * @return list<string> mensajes, uno por casilla en conflicto
     */
    public static function check(Section $section, array $grid): array
    {
        $section->loadMissing('grade');
        $teacherOf = TeacherAssignment::where('section_id', $section->id)->with('teacher')->get()->keyBy('subject_id');
        $teachers = $teacherOf->pluck('teacher_id')->unique();
        if ($teachers->isEmpty()) {
            return [];
        }

        // Clases de esos docentes en las demás secciones del año
        $elsewhere = TeacherAssignment::whereIn('teacher_id', $teachers)
            ->where('section_id', '!=', $section->id)
            ->whereHas('section', fn ($q) => $q->where('school_year_id', $section->school_year_id))
            ->get()
            ->mapWithKeys(fn ($a) => [$a->section_id.'|'.$a->subject_id => $a->teacher_id]);
        $others = ScheduleSlot::whereIn('section_id', $elsewhere->keys()->map(fn ($k) => (int) explode('|', $k)[0])->unique())
            ->with(['block', 'section.grade', 'subject'])
            ->get()
            ->filter(fn (ScheduleSlot $s) => $elsewhere->has($s->section_id.'|'.$s->subject_id))
            ->groupBy(fn (ScheduleSlot $s) => $elsewhere[$s->section_id.'|'.$s->subject_id].'|'.$s->weekday);

        $blocks = ScheduleBlock::whereIn('id', array_keys($grid))->get()->keyBy('id');
        $days = Timetable::days();
        $messages = [];

        foreach ($grid as $blockId => $row) {
            foreach ($row as $weekday => $subjectId) {
                $assignment = $teacherOf[$subjectId] ?? null;
                if (! $assignment || ! isset($blocks[$blockId])) {
                    continue;
                }
                /** @var Collection<int, ScheduleSlot> $candidates */
                $candidates = $others[$assignment->teacher_id.'|'.$weekday] ?? collect();
                $clash = $candidates->first(fn (ScheduleSlot $s) => $s->block->overlaps($blocks[$blockId]));
                if ($clash) {
                    $messages[] = sprintf('%s %s: %s ya tiene %s (%s) a esa hora.',
                        $days[$weekday] ?? '', $blocks[$blockId]->range(), $assignment->teacher->name,
                        $clash->section->label(), $clash->subject->name);
                }
            }
        }

        return $messages;
    }

    /** Choques del horario ya guardado (por ejemplo, tras cambiar una asignación docente). */
    public static function forSection(Section $section): array
    {
        $grid = [];
        foreach ($section->scheduleSlots()->get() as $slot) {
            $grid[$slot->schedule_block_id][$slot->weekday] = $slot->subject_id;
        }

        return self::check($section, $grid);
    }
}
