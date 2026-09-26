<?php

namespace Database\Seeders;

use App\Models\ScheduleBlock;
use App\Models\ScheduleSlot;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Support\Timetable;
use Illuminate\Database\Seeder;

/**
 * Horario de clases FICTICIO para desarrollo local (nunca en producción):
 * franjas por nivel y un horario por sección sin choques de docentes.
 */
class ScheduleDemoSeeder extends Seeder
{
    /** [inicio, fin, descanso] por nivel. */
    private const BLOCKS = [
        'preschool' => [['07:30', '08:15'], ['08:15', '09:00'], ['09:00', '09:30', 'Descanso y lonchera'], ['09:30', '10:15'], ['10:15', '11:00'], ['11:00', '11:45']],
        'primary' => [['07:00', '07:55'], ['07:55', '08:50'], ['08:50', '09:45'], ['09:45', '10:15', 'Descanso'], ['10:15', '11:10'], ['11:10', '12:05'], ['12:05', '13:00']],
        'secondary' => [['06:30', '07:25'], ['07:25', '08:20'], ['08:20', '09:15'], ['09:15', '09:45', 'Descanso'], ['09:45', '10:40'], ['10:40', '11:35'], ['11:35', '12:30']],
    ];

    public function run(): void
    {
        $year = SchoolYear::current();
        if (! $year) {
            return;
        }

        $blocks = [];
        foreach (self::BLOCKS as $level => $rows) {
            foreach ($rows as $row) {
                $blocks[$level][] = ScheduleBlock::create([
                    'school_year_id' => $year->id, 'level' => $level, 'starts_at' => $row[0], 'ends_at' => $row[1],
                    'is_break' => isset($row[2]), 'label' => $row[2] ?? null,
                ]);
            }
        }

        $days = array_keys(Timetable::days());
        $teachers = TeacherAssignment::whereHas('section', fn ($q) => $q->where('school_year_id', $year->id))->get()
            ->mapWithKeys(fn ($a) => [$a->section_id.'|'.$a->subject_id => $a->teacher_id]);
        $busy = []; // [docente][día] => [[inicio, fin], …]

        // Secundaria primero: sus docentes dictan en varias secciones
        $sections = $year->sections()->with('grade.subjects')->get()
            ->sortBy(fn (Section $s) => [$s->grade->level === 'secondary' ? 0 : 1, $s->grade->position, $s->name]);

        foreach ($sections as $section) {
            $classBlocks = collect($blocks[$section->grade->level])->reject->is_break->values();
            $capacity = $classBlocks->count() * count($days);
            $subjects = $section->grade->subjects;

            // Clases por materia según el plan (preescolar: repartidas por igual)
            $lessons = [];
            foreach ($subjects as $subject) {
                $hours = (int) $subject->pivot->weekly_hours ?: intdiv($capacity, max(1, $subjects->count()));
                $lessons[$subject->id] = $hours;
            }
            // Intercaladas para que cada materia se reparta en la semana
            $queue = [];
            while (array_sum($lessons) > 0 && count($queue) < $capacity) {
                foreach ($lessons as $id => $left) {
                    if ($left > 0 && count($queue) < $capacity) {
                        $queue[] = $id;
                        $lessons[$id]--;
                    }
                }
            }

            $taken = [];
            $perDay = [];
            $cursor = 0;
            foreach ($queue as $subjectId) {
                $teacher = $teachers[$section->id.'|'.$subjectId] ?? null;
                for ($try = 0; $try < $capacity; $try++) {
                    $n = ($cursor + $try) % $capacity;
                    $day = $days[$n % count($days)];
                    $block = $classBlocks[intdiv($n, count($days))];
                    $key = $block->id.'|'.$day;
                    if (isset($taken[$key]) || ($perDay[$subjectId][$day] ?? 0) >= 2) {
                        continue;
                    }
                    if ($teacher && collect($busy[$teacher][$day] ?? [])->contains(fn ($t) => $t[0] < $block->end() && $block->start() < $t[1])) {
                        continue;
                    }
                    $taken[$key] = true;
                    $perDay[$subjectId][$day] = ($perDay[$subjectId][$day] ?? 0) + 1;
                    if ($teacher) {
                        $busy[$teacher][$day][] = [$block->start(), $block->end()];
                    }
                    ScheduleSlot::create(['section_id' => $section->id, 'schedule_block_id' => $block->id, 'weekday' => $day, 'subject_id' => $subjectId]);
                    $cursor = $n + 1;
                    break;
                }
            }
        }
    }
}
