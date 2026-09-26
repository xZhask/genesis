<?php

namespace Tests\Feature\Portal;

use App\Enums\Role;
use App\Models\ScheduleBlock;
use App\Models\ScheduleSlot;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Horario de clases fijo para el año: el admin define franjas por nivel y
 * la cuadrícula de cada sección; docentes, estudiantes y acudientes lo
 * consultan (cada quien solo el suyo).
 */
class ScheduleTest extends PortalTestCase
{
    private User $admin;

    private ScheduleBlock $first;

    private ScheduleBlock $second;

    private ScheduleBlock $primaryFirst;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $block = fn (string $level, string $start, string $end, bool $break = false) => ScheduleBlock::create([
            'school_year_id' => $this->year->id, 'level' => $level, 'starts_at' => $start, 'ends_at' => $end,
            'is_break' => $break, 'label' => $break ? 'Descanso' : null,
        ]);
        $this->first = $block('secondary', '07:00', '07:55');
        $this->second = $block('secondary', '07:55', '08:50');
        $block('secondary', '08:50', '09:20', true);
        // Primaria empieza a otra hora: 7:30 se cruza con la primera franja de secundaria
        $this->primaryFirst = $block('primary', '07:30', '08:25');
    }

    private function saveGrid(array $slots, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->admin)->put(route('admin.academic.schedule.update', $this->seventh), ['slots' => $slots]);
    }

    public function test_admin_builds_a_section_schedule(): void
    {
        $this->actingAs($this->admin)->get(route('admin.academic.schedule.edit', $this->seventh))
            ->assertOk()->assertSee('Horario de 7.° 1')->assertSee('Descanso')->assertSee('Horas por materia');

        $this->saveGrid([$this->first->id => [1 => $this->math->id, 3 => $this->math->id], $this->second->id => [2 => '']])
            ->assertRedirect(route('admin.academic.schedule.edit', $this->seventh))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, ScheduleSlot::count());
        $this->assertEqualsCanonicalizing([1, 3], ScheduleSlot::pluck('weekday')->all());

        // Guardar de nuevo reemplaza el horario anterior
        $this->saveGrid([$this->second->id => [5 => $this->math->id]]);
        $this->assertSame(1, ScheduleSlot::count());
    }

    public function test_subjects_outside_the_grade_and_breaks_are_rejected(): void
    {
        $other = Subject::create(['name' => 'Química', 'area_id' => $this->math->area_id]);
        $this->saveGrid([$this->first->id => [1 => $other->id]])->assertSessionHasErrors('slots.*.*');

        $break = ScheduleBlock::where('is_break', true)->first();
        $this->saveGrid([$break->id => [1 => $this->math->id]])->assertSessionHasErrors('slots');
        $this->saveGrid([$this->primaryFirst->id => [1 => $this->math->id]])->assertSessionHasErrors('slots');

        $this->assertSame(0, ScheduleSlot::count());
    }

    public function test_a_teacher_cannot_be_in_two_sections_at_the_same_time(): void
    {
        // El mismo docente dicta Matemáticas en 3.° 1, que empieza a las 7:30 el lunes
        TeacherAssignment::where('section_id', $this->third->id)->update(['teacher_id' => $this->teacher->id]);
        ScheduleSlot::create(['section_id' => $this->third->id, 'schedule_block_id' => $this->primaryFirst->id, 'weekday' => 1, 'subject_id' => $this->math->id]);

        $this->saveGrid([$this->first->id => [1 => $this->math->id]])
            ->assertSessionHasErrors('conflicts');
        $this->assertSame(1, ScheduleSlot::count());

        // El martes no hay choque
        $this->saveGrid([$this->first->id => [2 => $this->math->id]])->assertSessionHasNoErrors();
        $this->assertSame(2, ScheduleSlot::count());
    }

    public function test_admin_edits_blocks_and_overlaps_are_rejected(): void
    {
        $route = route('admin.academic.schedule.blocks', [$this->year, 'secondary']);

        $this->actingAs($this->admin)->put($route, ['blocks' => [
            ['id' => $this->first->id, 'start' => '07:00', 'end' => '08:00'],
            ['id' => $this->second->id, 'start' => '07:55', 'end' => '08:50'],
        ]])->assertSessionHasErrors('blocks');

        $this->actingAs($this->admin)->put($route, ['blocks' => [
            ['id' => $this->first->id, 'start' => '09:00', 'end' => '08:00'],
        ]])->assertSessionHasErrors('blocks.0.end');

        ScheduleSlot::create(['section_id' => $this->seventh->id, 'schedule_block_id' => $this->second->id, 'weekday' => 1, 'subject_id' => $this->math->id]);
        $this->actingAs($this->admin)->put($route, ['blocks' => [
            ['id' => $this->first->id, 'start' => '06:30', 'end' => '07:25'],
            ['id' => $this->second->id, 'delete' => '1'],
            ['id' => '', 'start' => '10:00', 'end' => '10:30', 'is_break' => '1', 'label' => 'Almuerzo'],
            ['id' => '', 'start' => '', 'end' => ''],
        ]])->assertSessionHasNoErrors();

        $this->assertSame('06:30', $this->first->fresh()->start());
        $this->assertNull($this->second->fresh());
        $this->assertSame(0, ScheduleSlot::count(), 'Quitar una franja borra sus clases');
        $this->assertTrue(ScheduleBlock::where('label', 'Almuerzo')->where('is_break', true)->exists());
    }

    public function test_teacher_sees_their_week_across_sections(): void
    {
        Carbon::setTestNow(Carbon::parse('next monday')->setTime(7, 10));
        ScheduleSlot::create(['section_id' => $this->seventh->id, 'schedule_block_id' => $this->first->id, 'weekday' => 1, 'subject_id' => $this->math->id]);
        ScheduleSlot::create(['section_id' => $this->third->id, 'schedule_block_id' => $this->primaryFirst->id, 'weekday' => 2, 'subject_id' => $this->math->id]);

        $this->actingAs($this->teacher)->get(route('portal.teacher.schedule'))
            ->assertOk()
            ->assertSee('Mi horario')
            ->assertSee('7.° 1')
            ->assertSee('Ahora')
            ->assertDontSee('3.° 1'); // esa clase es de otro docente

        $this->actingAs($this->otherTeacher)->get(route('portal.teacher.schedule'))
            ->assertSee('3.° 1')->assertSee('7:30 – 8:25 a. m.')->assertDontSee('7.° 1');
    }

    public function test_homeroom_teacher_sees_the_group_schedule_only_for_their_group(): void
    {
        $this->seventh->update(['homeroom_teacher_id' => $this->otherTeacher->id]);
        ScheduleSlot::create(['section_id' => $this->seventh->id, 'schedule_block_id' => $this->first->id, 'weekday' => 1, 'subject_id' => $this->math->id]);

        $this->actingAs($this->otherTeacher)->get(route('portal.teacher.schedule', ['seccion' => $this->seventh->id]))
            ->assertOk()->assertSee('Horario de 7.° 1')->assertSee('Edgar Ojoalegre');
        $this->actingAs($this->teacher)->get(route('portal.teacher.schedule', ['seccion' => $this->seventh->id]))
            ->assertForbidden();
    }

    public function test_families_see_the_schedule_of_their_own_children_only(): void
    {
        ScheduleSlot::create(['section_id' => $this->seventh->id, 'schedule_block_id' => $this->first->id, 'weekday' => 1, 'subject_id' => $this->math->id]);
        $guardian = $this->guardianOf($this->andres);

        $this->actingAs($guardian)->get(route('portal.guardian.student', $this->andres))->assertSee('Ver horario');
        $this->actingAs($guardian)->get(route('portal.guardian.student.schedule', $this->andres))
            ->assertOk()->assertSee('Horario de Andrés')->assertSee('Matemáticas')->assertSee('Edgar Ojoalegre')->assertSee('Descanso');
        $this->actingAs($guardian)->get(route('portal.guardian.student.schedule', $this->lucia))->assertForbidden();

        $studentUser = User::factory()->role(Role::Student)->create();
        $this->andres->user()->associate($studentUser)->save();
        $this->actingAs($studentUser)->get(route('portal.student.schedule'))
            ->assertOk()->assertSee('Mi horario')->assertSee('Matemáticas');
    }

    public function test_only_admins_edit_schedules(): void
    {
        $guardian = $this->guardianOf($this->andres);
        $student = User::factory()->role(Role::Student)->create();

        foreach ([$this->teacher, $guardian, $student] as $user) {
            $this->actingAs($user)->get(route('admin.academic.schedule.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.academic.schedule.edit', $this->seventh))->assertForbidden();
            $this->saveGrid([$this->first->id => [1 => $this->math->id]], $user)->assertForbidden();
            $this->actingAs($user)->put(route('admin.academic.schedule.blocks', [$this->year, 'secondary']), ['blocks' => []])->assertForbidden();
        }

        $this->assertSame(0, ScheduleSlot::count());
        $this->assertSame(4, ScheduleBlock::count());
    }

    public function test_copying_another_section_only_prefills_the_form(): void
    {
        $other = $this->year->sections()->create(['grade_id' => $this->seventh->grade_id, 'name' => '2']);
        ScheduleSlot::create(['section_id' => $other->id, 'schedule_block_id' => $this->first->id, 'weekday' => 4, 'subject_id' => $this->math->id]);

        $this->actingAs($this->admin)->get(route('admin.academic.schedule.edit', [$this->seventh, 'copiar' => $other->id]))
            ->assertOk()->assertSee('Se copió el horario de 7.° 2');

        $this->assertSame(0, ScheduleSlot::where('section_id', $this->seventh->id)->count());
    }

    public function test_empty_schedule_message(): void
    {
        $this->actingAs($this->teacher)->get(route('portal.teacher.schedule'))
            ->assertOk()->assertSee('todavía no ha cargado el horario');
    }
}
