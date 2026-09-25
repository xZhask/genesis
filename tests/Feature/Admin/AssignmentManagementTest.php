<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Tests\Feature\Portal\PortalTestCase;

/** Asignaciones docentes en Admin → Académico. */
class AssignmentManagementTest extends PortalTestCase
{
    public function test_admin_assigns_teachers_and_homeroom(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.academic.assignments.index', ['seccion' => $this->third->id]))
            ->assertOk()
            ->assertSee('3.° 1 · 2026')
            ->assertSee('Director de grupo');

        $this->actingAs($admin)->put(route('admin.academic.assignments.update', $this->third), [
            'homeroom_teacher_id' => $this->teacher->id,
            'teachers' => [$this->math->id => $this->teacher->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame($this->teacher->id, $this->third->fresh()->homeroom_teacher_id);
        $this->assertSame($this->teacher->id, TeacherAssignment::where('section_id', $this->third->id)->value('teacher_id'));
        $this->assertSame(1, TeacherAssignment::where('section_id', $this->third->id)->count());
    }

    public function test_clearing_a_subject_removes_the_assignment(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.academic.assignments.update', $this->seventh), [
            'teachers' => [$this->math->id => ''],
        ])->assertSessionHasNoErrors();

        $this->assertModelMissing($this->assignment);
    }

    public function test_only_active_teachers_can_be_assigned(): void
    {
        $admin = User::factory()->admin()->create();
        $guardian = User::factory()->role(Role::Guardian)->create();
        $inactive = User::factory()->role(Role::Teacher)->inactive()->create();

        foreach ([$guardian, $inactive, $admin] as $candidate) {
            $this->actingAs($admin)->put(route('admin.academic.assignments.update', $this->seventh), [
                'teachers' => [$this->math->id => $candidate->id],
            ])->assertSessionHasErrors('teachers.'.$this->math->id);
        }

        $this->assertSame($this->teacher->id, $this->assignment->fresh()->teacher_id);
    }

    public function test_subjects_outside_the_grade_plan_are_ignored(): void
    {
        $admin = User::factory()->admin()->create();
        $other = Subject::create(['name' => 'Filosofía', 'area_id' => $this->math->area_id]);

        $this->actingAs($admin)->put(route('admin.academic.assignments.update', $this->seventh), [
            'teachers' => [$this->math->id => $this->teacher->id, $other->id => $this->teacher->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, TeacherAssignment::where('subject_id', $other->id)->count());
    }

    public function test_staff_page_lists_the_teachers_classes(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.people.staff.edit', $this->teacher))
            ->assertOk()
            ->assertSee('Matemáticas · 7.° 1');
    }
}
