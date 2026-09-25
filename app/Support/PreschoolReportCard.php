<?php

namespace App\Support;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\DescriptiveEvaluation;
use App\Models\Enrollment;
use App\Models\Period;
use App\Models\PeriodReport;
use Illuminate\Support\Collection;

/**
 * Boletín de preescolar (provisional, hasta tener el modelo del colegio):
 * una descripción por dimensión del desarrollo, observaciones de la docente
 * y asistencia. Sin notas ni promedios.
 */
class PreschoolReportCard
{
    public function __construct(public readonly Enrollment $enrollment, public readonly Period $period)
    {
        $enrollment->loadMissing(['student', 'section.grade', 'section.homeroomTeacher', 'section.assignments.teacher', 'schoolYear.periods']);
    }

    public function isFinal(): bool
    {
        return $this->period->number === $this->enrollment->schoolYear->periods->max('number');
    }

    /** @return Collection<int, array{name: string, teacher: ?string, text: ?string}> */
    public function dimensions(): Collection
    {
        $texts = DescriptiveEvaluation::where('enrollment_id', $this->enrollment->id)
            ->where('period_id', $this->period->id)
            ->pluck('text', 'subject_id');
        $teachers = $this->enrollment->section->assignments->mapWithKeys(fn ($a) => [$a->subject_id => $a->teacher->name]);

        return $this->enrollment->section->grade->subjects()->get()->map(fn ($subject) => [
            'name' => $subject->name,
            'teacher' => $teachers[$subject->id] ?? null,
            'text' => $texts[$subject->id] ?? null,
        ]);
    }

    public function report(): ?PeriodReport
    {
        return PeriodReport::where('enrollment_id', $this->enrollment->id)->where('period_id', $this->period->id)->first();
    }

    public function attendance(): array
    {
        // En preescolar la asistencia se toma por dimensión: se cuentan los días con alguna falta
        $days = Attendance::where('enrollment_id', $this->enrollment->id)
            ->where('period_id', $this->period->id)
            ->get(['date', 'status'])
            ->groupBy(fn ($a) => $a->date->toDateString());

        return collect(AttendanceStatus::cases())
            ->mapWithKeys(fn ($s) => [$s->value => $days->filter(fn ($records) => $records->contains(fn ($r) => $r->status === $s))->count()])
            ->all();
    }
}
