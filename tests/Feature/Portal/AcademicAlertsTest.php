<?php

namespace Tests\Feature\Portal;

use App\Enums\AttendanceStatus;
use App\Enums\Role;
use App\Models\Attendance;
use App\Models\GradeItem;
use App\Models\Period;
use App\Models\Score;
use App\Models\Student;
use App\Models\User;
use App\Support\AcademicAlerts;
use Illuminate\Support\Carbon;

/**
 * Alertas (fase 3): cumpleaños, bajo rendimiento e inasistencia. Las ven el
 * docente (sus clases), el director de grupo y el admin; las familias no.
 */
class AcademicAlertsTest extends PortalTestCase
{
    private function period(int $index = 3): Period
    {
        return $this->year->periods[$index];
    }

    private function grade(Student $student, array $values, int $period = 3, ?int $sectionId = null): void
    {
        foreach ($values as $n => $value) {
            $item = GradeItem::firstOrCreate([
                'section_id' => $sectionId ?? $this->seventh->id, 'subject_id' => $this->math->id,
                'period_id' => $this->period($period)->id, 'component' => 'knowing', 'name' => "Actividad {$n}",
            ]);
            Score::updateOrCreate(['grade_item_id' => $item->id, 'enrollment_id' => $this->enrollmentId($student)], ['value' => $value]);
        }
    }

    private function absent(Student $student, int $times, AttendanceStatus $status = AttendanceStatus::Absent): void
    {
        for ($i = 1; $i <= $times; $i++) {
            Attendance::create([
                'enrollment_id' => $this->enrollmentId($student), 'subject_id' => $this->math->id,
                'period_id' => $this->period()->id, 'date' => today()->subDays($i),
                'status' => $status, 'recorded_by' => $this->teacher->id,
            ]);
        }
    }

    public function test_low_performance_needs_enough_graded_activities(): void
    {
        $this->grade($this->andres, [2.0]);
        $alerts = AcademicAlerts::forTeacher($this->teacher, $this->year);
        $this->assertTrue($alerts->lowPerformance()->isEmpty(), 'Una sola nota no basta para alertar');

        $this->grade($this->andres, [2.0, 2.6]);
        $this->grade($this->sara, [4.0, 4.5]);
        $low = AcademicAlerts::forTeacher($this->teacher, $this->year)->lowPerformance();

        $this->assertCount(1, $low);
        $this->assertSame($this->andres->id, $low[0]['enrollment']->student_id);
        $this->assertSame(2.3, $low[0]['score']);
        $this->assertFalse($low[0]['year']);
    }

    public function test_year_alert_when_the_accumulated_can_no_longer_pass(): void
    {
        // Periodos 1 a 3 cerrados con 1,0: con el 25 % restante ya no alcanza 3,0
        foreach ([0, 1, 2] as $index) {
            $this->grade($this->andres, [1.0], $index);
            $this->grade($this->sara, [4.0], $index);
            $this->period($index)->close(User::factory()->admin()->create());
        }

        $low = AcademicAlerts::forTeacher($this->teacher, $this->year)->lowPerformance();

        $this->assertCount(1, $low);
        $this->assertTrue($low[0]['year']);
        $this->assertNull($low[0]['score']);
    }

    public function test_absences_count_only_unexcused_ones_from_the_threshold(): void
    {
        config(['school.alerts.absences' => 3]);
        $this->absent($this->andres, 2);
        $this->absent($this->sara, 5, AttendanceStatus::Excused);
        $this->assertTrue(AcademicAlerts::forTeacher($this->teacher, $this->year)->absences()->isEmpty());

        Attendance::create([
            'enrollment_id' => $this->enrollmentId($this->andres), 'subject_id' => $this->math->id,
            'period_id' => $this->period()->id, 'date' => today(), 'status' => AttendanceStatus::Absent, 'recorded_by' => $this->teacher->id,
        ]);
        $absences = AcademicAlerts::forTeacher($this->teacher, $this->year)->absences();

        $this->assertCount(1, $absences);
        $this->assertSame(3, $absences[0]['count']);
    }

    public function test_birthdays_of_the_next_days(): void
    {
        $this->andres->update(['birth_date' => today()->subYears(12)]);
        $this->sara->update(['birth_date' => today()->addDays(3)->subYears(12)]);
        $this->lucia->update(['birth_date' => today()->addDays(2)->subYears(8)]); // otra sección

        $birthdays = AcademicAlerts::forTeacher($this->teacher, $this->year)->birthdays();

        $this->assertSame(['Andrés', 'Sara'], $birthdays->map(fn ($b) => $b['enrollment']->student->first_names)->all());
        $this->assertSame([0, 3], $birthdays->pluck('days')->all());
    }

    public function test_february_29_birthdays_are_celebrated_on_the_28th(): void
    {
        Carbon::setTestNow('2027-02-20 10:00');

        $this->assertSame('2027-02-28', AcademicAlerts::nextBirthday(Carbon::parse('2016-02-29'))->toDateString());
    }

    public function test_teacher_only_sees_alerts_of_their_classes(): void
    {
        $this->grade($this->andres, [1.5, 2.0]);
        $this->grade($this->lucia, [1.0, 1.0], sectionId: $this->third->id);

        $this->actingAs($this->teacher)->get(route('portal.teacher.home'))
            ->assertOk()
            ->assertSee('Alertas: 1 en bajo rendimiento')
            ->assertSee('Pérez Díaz, Andrés')
            ->assertDontSee('Pérez Díaz, Lucía');

        $this->actingAs($this->otherTeacher)->get(route('portal.teacher.home'))
            ->assertSee('Pérez Díaz, Lucía')
            ->assertDontSee('Pérez Díaz, Andrés');
    }

    public function test_homeroom_teacher_sees_every_subject_of_the_group(): void
    {
        $this->seventh->update(['homeroom_teacher_id' => $this->otherTeacher->id]);
        $this->grade($this->andres, [1.5, 2.0]);

        $this->actingAs($this->otherTeacher)->get(route('portal.teacher.homeroom'))
            ->assertOk()
            ->assertSee('Bajo rendimiento')
            ->assertSee('Pérez Díaz, Andrés');
    }

    public function test_admin_sees_the_summary_and_families_never_see_alerts(): void
    {
        $this->grade($this->andres, [1.5, 2.0]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.academic.alerts'))
            ->assertOk()->assertSee('Resumen por sección')->assertSee('Pérez Díaz, Andrés');
        $this->actingAs($admin)->get(route('admin.academic.alerts', ['seccion' => $this->third->id]))
            ->assertOk()->assertDontSee('Pérez Díaz, Andrés');
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee('1 estudiante con alertas');

        $guardian = $this->guardianOf($this->andres);
        $this->actingAs($guardian)->get(route('portal.guardian.student', $this->andres))
            ->assertOk()->assertDontSee('Bajo rendimiento');

        foreach ([$guardian, $this->teacher, User::factory()->role(Role::Student)->create()] as $user) {
            $this->actingAs($user)->get(route('admin.academic.alerts'))->assertForbidden();
        }
    }

    public function test_absence_threshold_is_editable_in_settings(): void
    {
        $this->assertSame(3, config('school.alerts.absences'));
        $this->absent($this->andres, 2);
        config(['school.alerts.absences' => 2]);

        $this->assertCount(1, AcademicAlerts::forTeacher($this->teacher, $this->year)->absences());
    }
}
