<?php

namespace Tests\Feature\Portal;

use App\Enums\AttendanceStatus;
use App\Enums\Role;
use App\Models\Attendance;
use App\Models\Guardian;
use App\Models\User;

/**
 * Portales del acudiente y del estudiante (regla de seguridad 1): el
 * acudiente solo ve a sus acudidos y el estudiante solo a sí mismo.
 */
class FamilyPortalTest extends PortalTestCase
{
    private function absence(int $enrollmentId, string $status = 'absent', int $daysAgo = 1): void
    {
        $date = today()->subDays($daysAgo);
        Attendance::create([
            'enrollment_id' => $enrollmentId,
            'subject_id' => $this->math->id,
            'period_id' => $this->year->periodFor($date)->id,
            'date' => $date,
            'status' => AttendanceStatus::from($status),
            'recorded_by' => $this->teacher->id,
        ]);
    }

    public function test_guardian_with_several_children_chooses_and_sees_each_one(): void
    {
        $guardian = $this->guardianOf($this->andres, $this->lucia);
        $this->absence($this->enrollmentId($this->andres));
        $this->absence($this->enrollmentId($this->andres), 'late', 2);

        $this->actingAs($guardian)->get(route('portal.guardian.home'))
            ->assertOk()
            ->assertSee('Andrés Pérez Díaz')
            ->assertSee('Lucía Pérez Díaz')
            ->assertSee('1 falta en el año')
            ->assertDontSee('Sara');

        $this->actingAs($guardian)->get(route('portal.guardian.student', $this->andres))
            ->assertOk()
            ->assertSee('7.° 1')
            ->assertSee('Matemáticas')
            ->assertSee('Edgar Ojoalegre')
            ->assertSee('Ausente')
            ->assertSee('Tarde')
            ->assertSee('Lucía'); // cambio rápido entre acudidos
    }

    public function test_guardian_with_one_child_goes_straight_to_their_page(): void
    {
        $guardian = $this->guardianOf($this->lucia);

        $this->actingAs($guardian)->get(route('portal.guardian.home'))
            ->assertRedirect(route('portal.guardian.student', $this->lucia));

        $this->actingAs($guardian)->get(route('portal.guardian.student', $this->lucia))
            ->assertOk()
            ->assertSee('Sin faltas ni llegadas tarde');
    }

    public function test_guardian_cannot_see_someone_elses_child(): void
    {
        $guardian = $this->guardianOf($this->andres);

        $this->actingAs($guardian)->get(route('portal.guardian.student', $this->sara))->assertForbidden();
        $this->actingAs($guardian)->get(route('portal.guardian.student', $this->lucia))->assertForbidden();
    }

    public function test_guardian_account_without_a_guardian_record_gets_403(): void
    {
        $user = User::factory()->role(Role::Guardian)->create();

        $this->actingAs($user)->get(route('portal.guardian.home'))->assertForbidden();
        $this->actingAs($user)->get(route('portal.guardian.student', $this->andres))->assertForbidden();
    }

    public function test_student_sees_only_themselves(): void
    {
        $user = User::factory()->role(Role::Student)->create();
        $this->andres->user()->associate($user)->save();
        $this->absence($this->enrollmentId($this->andres));

        $this->actingAs($user)->get(route('portal.student.home'))
            ->assertOk()
            ->assertSee('Hola, Andrés')
            ->assertSee('Matemáticas')
            ->assertDontSee('Sara');

        // No hay URL con identificador: las del acudiente le responden 403
        $this->actingAs($user)->get(route('portal.guardian.student', $this->sara))->assertForbidden();
        $this->actingAs($user)->get(route('portal.guardian.student', $this->andres))->assertForbidden();
        $this->actingAs($user)->get(route('portal.guardian.home'))->assertForbidden();
    }

    public function test_family_roles_get_403_on_teacher_and_admin_urls(): void
    {
        $guardian = $this->guardianOf($this->andres);
        $student = User::factory()->role(Role::Student)->create();
        $this->sara->user()->associate($student)->save();

        foreach ([$guardian, $student] as $user) {
            $this->actingAs($user)->get(route('portal.teacher.attendance'))->assertForbidden();
            $this->actingAs($user)->put(route('portal.teacher.attendance.save'), [
                'clase' => $this->assignment->id, 'fecha' => today()->toDateString(),
                'statuses' => [$this->enrollmentId($this->andres) => 'present'],
            ])->assertForbidden();
            $this->actingAs($user)->get(route('admin.people.students.edit', $this->andres))->assertForbidden();
            $this->actingAs($user)->get(route('admin.academic.assignments.index'))->assertForbidden();
        }

        $this->assertSame(0, Attendance::count());
    }

    public function test_teacher_who_is_also_a_guardian_sees_their_children(): void
    {
        $guardian = Guardian::factory()->create(['user_id' => $this->otherTeacher->id]);
        $this->lucia->guardians()->attach($guardian->id, ['relationship' => 'mother', 'is_primary' => true]);

        $this->actingAs($this->otherTeacher)->get(route('portal.teacher.home'))->assertOk()->assertSee('Mis acudidos');
        $this->actingAs($this->otherTeacher)->get(route('portal.guardian.student', $this->lucia))->assertOk();
        $this->actingAs($this->otherTeacher)->get(route('portal.guardian.student', $this->andres))->assertForbidden();

        // Un docente sin acudidos no entra al portal del acudiente
        $this->actingAs($this->teacher)->get(route('portal.guardian.home'))->assertForbidden();
    }

    public function test_student_policy_lets_teachers_see_only_their_sections(): void
    {
        $this->assertTrue($this->teacher->can('view', $this->andres));
        $this->assertFalse($this->teacher->can('view', $this->lucia));
        $this->assertFalse($this->teacher->can('update', $this->andres));

        $this->third->update(['homeroom_teacher_id' => $this->teacher->id]);
        $this->assertTrue($this->teacher->can('view', $this->lucia), 'El director de grupo ve a sus estudiantes');
    }
}
