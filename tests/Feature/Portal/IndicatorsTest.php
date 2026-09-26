<?php

namespace Tests\Feature\Portal;

use App\Enums\AttendanceStatus;
use App\Enums\Role;
use App\Models\Attendance;
use App\Models\GradeItem;
use App\Models\Score;
use App\Models\Student;
use App\Models\User;
use App\Support\Indicators;
use Illuminate\Support\Carbon;

/**
 * Gráficos (fase 3): asistencia por día y por sección, aprobados por clase y
 * aprobación por grado. Los ve el docente (sus clases) y el admin.
 */
class IndicatorsTest extends PortalTestCase
{
    private function attendance(Student $student, string $status, Carbon $date, ?int $sectionSubject = null): void
    {
        Attendance::create([
            'enrollment_id' => $this->enrollmentId($student), 'subject_id' => $sectionSubject ?? $this->math->id,
            'period_id' => $this->year->periodFor($date)->id, 'date' => $date,
            'status' => AttendanceStatus::from($status), 'recorded_by' => $this->teacher->id,
        ]);
    }

    private function grade(Student $student, float $value, int $period, ?int $sectionId = null): void
    {
        $item = GradeItem::firstOrCreate([
            'section_id' => $sectionId ?? $this->seventh->id, 'subject_id' => $this->math->id,
            'period_id' => $this->year->periods[$period]->id, 'component' => 'knowing', 'name' => 'Evaluación',
        ]);
        Score::create(['grade_item_id' => $item->id, 'enrollment_id' => $this->enrollmentId($student), 'value' => $value]);
    }

    public function test_attendance_counts_present_and_late_as_attended(): void
    {
        $day = today()->subDay();
        $this->attendance($this->andres, 'present', $day);
        $this->attendance($this->sara, 'late', $day);
        $this->attendance($this->andres, 'absent', $day->copy()->subDay());
        $this->attendance($this->sara, 'excused', $day->copy()->subDay());

        $days = Indicators::attendanceByDay($this->teacher->assignmentsIn($this->year), today()->startOfMonth());

        $this->assertSame([0.0, 100.0], $days->pluck('percent')->all());
        $this->assertSame(50.0, Indicators::total($days));
        $this->assertSame('50 %', Indicators::percent(50.0));
        $this->assertSame('94,5 %', Indicators::percent(94.5));
    }

    public function test_teacher_summary_only_counts_their_classes(): void
    {
        $this->attendance($this->andres, 'present', today()->subDay());
        $this->attendance($this->lucia, 'absent', today()->subDay()); // clase de otro docente
        $this->grade($this->andres, 4.5, 3);
        $this->grade($this->sara, 2.0, 3);

        $this->actingAs($this->teacher)->get(route('portal.teacher.summary'))
            ->assertOk()
            ->assertSee('Resumen de mis clases')
            ->assertSee('100 %')
            ->assertSee('En total: 1 aprueba, 1 no aprueba y 0 sin nota.')
            ->assertSee('7.° 1 · Matemáticas')
            ->assertDontSee('3.° 1 · Matemáticas');
    }

    public function test_months_filter_stays_inside_the_school_year(): void
    {
        $months = Indicators::months($this->year);

        $this->assertSame(today()->format('Y-m'), $months->first()->format('Y-m'));
        $this->assertSame('2026-01', $months->last()->format('Y-m'));
        $this->assertSame('2026-03', Indicators::month($this->year, '2026-03')->format('Y-m'));
        $this->assertSame(today()->format('Y-m'), Indicators::month($this->year, '2031-01')->format('Y-m'));
    }

    public function test_admin_sees_attendance_by_section_and_approval_by_grade(): void
    {
        $this->attendance($this->andres, 'present', today()->subDay());
        $this->attendance($this->lucia, 'absent', today()->subDay());

        $this->grade($this->andres, 4.0, 0);
        $this->grade($this->sara, 2.0, 0);
        $this->grade($this->lucia, 3.5, 0, $this->third->id);
        $admin = User::factory()->admin()->create();
        $this->year->periods[0]->close($admin);

        $approval = Indicators::approvalByGrade($this->year->fresh());
        $cells = $approval['rows']->keyBy('grade');
        $first = $this->year->periods[0]->id;
        $this->assertSame(50.0, $cells['7.°']['cells'][$first]['percent']);
        $this->assertSame(100.0, $cells['3.°']['cells'][$first]['percent']);

        $this->actingAs($admin)->get(route('admin.academic.indicators'))
            ->assertOk()
            ->assertSee('Asistencia por sección')
            ->assertSee('3.° 1')
            ->assertSee('Aprobación por grado y periodo')
            ->assertSee('data-step="5"', false);
    }

    public function test_approval_waits_for_a_closed_period(): void
    {
        $this->grade($this->andres, 4.0, 3);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.academic.indicators'))
            ->assertOk()->assertSee('Aparece cuando se cierra el primer periodo');
    }

    public function test_only_teachers_and_admins_see_indicators(): void
    {
        $guardian = $this->guardianOf($this->andres);
        $student = User::factory()->role(Role::Student)->create();

        foreach ([$guardian, $student] as $user) {
            $this->actingAs($user)->get(route('portal.teacher.summary'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.academic.indicators'))->assertForbidden();
        }
        $this->actingAs($this->teacher)->get(route('admin.academic.indicators'))->assertForbidden();
    }

    public function test_teacher_without_classes_sees_an_empty_state(): void
    {
        $this->actingAs(User::factory()->role(Role::Teacher)->create())->get(route('portal.teacher.summary'))
            ->assertOk()->assertSee('Cuando tengas clases asignadas');
    }
}
