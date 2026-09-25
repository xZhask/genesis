<?php

namespace Tests\Feature\Portal;

use App\Enums\AttendanceStatus;
use App\Enums\Role;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Asistencia por materia desde el portal docente. Reglas de seguridad 1 y 3:
 * cada docente solo escribe en sus clases, y un periodo cerrado es de solo
 * lectura en el servidor.
 */
class AttendanceTest extends PortalTestCase
{
    private function save(User $user, int $assignmentId, string $date, array $statuses)
    {
        return $this->actingAs($user)->put(route('portal.teacher.attendance.save'), [
            'clase' => $assignmentId,
            'fecha' => $date,
            'statuses' => $statuses,
        ]);
    }

    public function test_teacher_home_lists_only_their_classes(): void
    {
        $this->actingAs($this->teacher)->get(route('portal.teacher.home'))
            ->assertOk()
            ->assertSee('Hola, Edgar')
            ->assertSee('7.° 1')
            ->assertSee('Asistencia de hoy pendiente')
            ->assertDontSee('3.° 1');
    }

    public function test_attendance_sheet_lists_active_students_all_present_by_default(): void
    {
        $this->actingAs($this->teacher)->get(route('portal.teacher.attendance'))
            ->assertOk()
            ->assertSee('Matemáticas · 7.° 1')
            ->assertSeeInOrder(['Arrieta Meza, Sara', 'Pérez Díaz, Andrés'])
            ->assertDontSee('Lucía')
            ->assertSee('Todos aparecen como presentes')
            ->assertSee('Guardar asistencia');
    }

    public function test_teacher_saves_and_corrects_attendance_without_duplicates(): void
    {
        $today = today()->toDateString();

        $this->save($this->teacher, $this->assignment->id, $today, [
            $this->enrollmentId($this->andres) => 'absent',
            $this->enrollmentId($this->sara) => 'present',
        ])->assertSessionHas('status_message', fn ($m) => str_contains($m, '1 ausente.'));

        $this->save($this->teacher, $this->assignment->id, $today, [
            $this->enrollmentId($this->andres) => 'excused',
            $this->enrollmentId($this->sara) => 'late',
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, Attendance::count());
        $record = Attendance::firstWhere('enrollment_id', $this->enrollmentId($this->andres));
        $this->assertSame(AttendanceStatus::Excused, $record->status);
        $this->assertSame($this->year->periods[3]->id, $record->period_id);
        $this->assertSame($this->teacher->id, $record->recorded_by);

        $this->actingAs($this->teacher)->get(route('portal.teacher.home'))->assertSee('Asistencia de hoy tomada');
    }

    public function test_teacher_cannot_write_in_another_teachers_class(): void
    {
        $this->save($this->teacher, $this->otherAssignment->id, today()->toDateString(), [
            $this->enrollmentId($this->lucia) => 'absent',
        ])->assertForbidden();

        $this->assertSame(0, Attendance::count());
    }

    public function test_asking_for_another_teachers_class_shows_your_own(): void
    {
        $this->actingAs($this->teacher)->get(route('portal.teacher.attendance', ['clase' => $this->otherAssignment->id]))
            ->assertOk()
            ->assertSee('Matemáticas · 7.° 1')
            ->assertDontSee('Lucía');
    }

    public function test_students_from_another_section_are_rejected(): void
    {
        $this->save($this->teacher, $this->assignment->id, today()->toDateString(), [
            $this->enrollmentId($this->andres) => 'present',
            $this->enrollmentId($this->lucia) => 'absent',
        ])->assertSessionHasErrors('statuses');

        $this->assertSame(0, Attendance::count());
    }

    public function test_closed_period_is_read_only_on_the_server(): void
    {
        $admin = User::factory()->admin()->create();
        $closed = $this->year->periods[2];
        $closed->close($admin);
        $date = $closed->starts_on->copy()->addDays(3)->toDateString();

        $this->save($this->teacher, $this->assignment->id, $date, [
            $this->enrollmentId($this->andres) => 'absent',
            $this->enrollmentId($this->sara) => 'absent',
        ])->assertSessionHasErrors(['period']);
        $this->assertSame(0, Attendance::count());

        $this->actingAs($this->teacher)->get(route('portal.teacher.attendance', ['clase' => $this->assignment->id, 'fecha' => $date]))
            ->assertOk()
            ->assertSee('está cerrado: esta asistencia es de solo lectura')
            ->assertSee('<fieldset disabled', false)
            ->assertDontSee('Guardar asistencia');

        // Solo el admin reabre, y entonces sí se puede corregir
        $closed->reopen($admin);
        $this->save($this->teacher, $this->assignment->id, $date, [
            $this->enrollmentId($this->andres) => 'absent',
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, Attendance::count());
    }

    public function test_existing_records_of_a_closed_period_cannot_be_changed(): void
    {
        $period = $this->year->periods[3];
        $today = today()->toDateString();
        $this->save($this->teacher, $this->assignment->id, $today, [$this->enrollmentId($this->andres) => 'present']);

        $period->close(User::factory()->admin()->create());

        $this->save($this->teacher, $this->assignment->id, $today, [$this->enrollmentId($this->andres) => 'absent'])
            ->assertSessionHasErrors('period');
        $this->assertSame(AttendanceStatus::Present, Attendance::first()->status);
    }

    public function test_future_dates_and_dates_outside_the_periods_are_rejected(): void
    {
        $this->save($this->teacher, $this->assignment->id, today()->addDay()->toDateString(), [
            $this->enrollmentId($this->andres) => 'absent',
        ])->assertSessionHasErrors('fecha');

        $this->save($this->teacher, $this->assignment->id, '2026-01-10', [
            $this->enrollmentId($this->andres) => 'absent',
        ])->assertSessionHasErrors('date');

        $this->save($this->teacher, $this->assignment->id, today()->toDateString(), [
            $this->enrollmentId($this->andres) => 'dormido',
        ])->assertSessionHasErrors('statuses.'.$this->enrollmentId($this->andres));

        $this->assertSame(0, Attendance::count());
    }

    public function test_inactive_teacher_cannot_record(): void
    {
        $this->teacher->forceFill(['is_active' => false])->save();

        $this->save($this->teacher, $this->assignment->id, today()->toDateString(), [
            $this->enrollmentId($this->andres) => 'absent',
        ])->assertForbidden();
    }

    public function test_the_last_selected_class_is_remembered(): void
    {
        $second = $this->assignment->replicate();
        $second->subject_id = Subject::create(['name' => 'Estadística', 'area_id' => $this->math->area_id])->id;
        $second->save();

        $this->actingAs($this->teacher)->get(route('portal.teacher.attendance', ['clase' => $second->id]))
            ->assertCookie('asistencia_clase', (string) $second->id);

        $this->actingAs($this->teacher)
            ->withCookie('asistencia_clase', (string) $second->id)
            ->get(route('portal.teacher.attendance'))
            ->assertSee('<option value="'.$second->id.'" selected', false);
    }

    public static function familyRoles(): array
    {
        return ['acudiente' => [Role::Guardian], 'estudiante' => [Role::Student], 'admin' => [Role::Admin]];
    }

    #[DataProvider('familyRoles')]
    public function test_other_roles_get_403_on_the_teacher_portal(Role $role): void
    {
        $user = User::factory()->role($role)->create();

        $this->actingAs($user)->get(route('portal.teacher.home'))->assertForbidden();
        $this->actingAs($user)->get(route('portal.teacher.attendance'))->assertForbidden();
        $this->save($user, $this->assignment->id, today()->toDateString(), [
            $this->enrollmentId($this->andres) => 'absent',
        ])->assertForbidden();

        $this->assertSame(0, Attendance::count());
    }
}
