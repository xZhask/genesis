<?php

namespace App\Support;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\GradingScale;
use App\Models\Period;
use App\Models\PeriodResult;
use App\Models\Section;

/**
 * Al cerrar un periodo, sus notas quedan congeladas en period_results (nota
 * por materia, desempeño, componentes y faltas). Mientras está abierto, se
 * calculan en vivo con ClassGrades. Si el admin lo reabre, se borran y se
 * vuelven a congelar al cerrarlo otra vez.
 */
class PeriodResults
{
    public static function freeze(Period $period): int
    {
        $year = $period->schoolYear;
        $scale = GradingScale::forYear($year);
        $count = 0;

        $sections = Section::where('school_year_id', $year->id)->with(['grade.subjects', 'enrollments'])->get();

        foreach ($sections as $section) {
            // Preescolar se evalúa de forma descriptiva, sin notas numéricas
            if ($section->grade->isPreschool() || $section->enrollments->isEmpty()) {
                continue;
            }

            $absences = Attendance::whereIn('enrollment_id', $section->enrollments->pluck('id'))
                ->where('period_id', $period->id)
                ->where('status', AttendanceStatus::Absent)
                ->selectRaw('enrollment_id, subject_id, count(*) as total')
                ->groupBy('enrollment_id', 'subject_id')
                ->get()
                ->keyBy(fn ($row) => $row->enrollment_id.'|'.$row->subject_id);

            foreach ($section->grade->subjects as $subject) {
                $grades = new ClassGrades($section, $subject, $period, $scale);

                foreach ($section->enrollments as $enrollment) {
                    $result = $grades->forEnrollment($enrollment->id);
                    PeriodResult::updateOrCreate(
                        ['enrollment_id' => $enrollment->id, 'subject_id' => $subject->id, 'period_id' => $period->id],
                        [
                            'knowing' => $result['knowing'],
                            'doing' => $result['doing'],
                            'being' => $result['being'],
                            'score' => $result['score'],
                            'performance' => $result['performance'],
                            'absences' => (int) ($absences[$enrollment->id.'|'.$subject->id]->total ?? 0),
                        ],
                    );
                    $count++;
                }
            }
        }

        return $count;
    }

    public static function clear(Period $period): void
    {
        PeriodResult::where('period_id', $period->id)->delete();
    }
}
