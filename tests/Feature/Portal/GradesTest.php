<?php

namespace Tests\Feature\Portal;

use App\Enums\EvaluationComponent;
use App\Enums\Performance;
use App\Enums\Role;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\GradingScale;
use App\Models\PeriodObjective;
use App\Models\PeriodReport;
use App\Models\PeriodResult;
use App\Models\Score;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Support\ClassGrades;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Notas por periodo: cálculo en el servidor, planilla del docente, logros,
 * comportamiento y congelado al cerrar. Reglas de seguridad 1 y 3.
 */
class GradesTest extends PortalTestCase
{
    private function period(int $index = 3)
    {
        return $this->year->periods[$index];
    }

    private function item(string $component, string $name = 'Taller', int $period = 3): GradeItem
    {
        return GradeItem::create([
            'section_id' => $this->seventh->id, 'subject_id' => $this->math->id,
            'period_id' => $this->period($period)->id, 'component' => $component, 'name' => $name,
        ]);
    }

    private function grade(GradeItem $item, $student, float $value): void
    {
        Score::create(['grade_item_id' => $item->id, 'enrollment_id' => $this->enrollmentId($student), 'value' => $value]);
    }

    public static function boundaries(): array
    {
        return [
            [1.0, Performance::Low], [2.99, Performance::Low], [2.995, Performance::Basic], [3.0, Performance::Basic],
            [4.19, Performance::Basic], [4.2, Performance::High], [4.79, Performance::High], [4.8, Performance::Superior], [5.0, Performance::Superior],
        ];
    }

    #[DataProvider('boundaries')]
    public function test_performance_ranges_follow_the_scale(float $score, Performance $expected): void
    {
        $this->assertSame($expected, GradingScale::forYear($this->year)->performanceFor($score));
    }

    public function test_period_score_is_the_weighted_average_of_components(): void
    {
        $scale = GradingScale::forYear($this->year);
        $scale->update(['weight_knowing' => 40, 'weight_doing' => 40, 'weight_being' => 20]);

        $this->grade($this->item('knowing', 'Evaluación'), $this->andres, 3.0);
        $this->grade($this->item('knowing', 'Quiz'), $this->andres, 4.0);   // saber = 3,5
        $this->grade($this->item('doing'), $this->andres, 4.5);             // hacer = 4,5
        $this->grade($this->item('being'), $this->andres, 5.0);             // ser = 5,0

        $result = (new ClassGrades($this->seventh, $this->math, $this->period(), $scale->fresh()))->forEnrollment($this->enrollmentId($this->andres));

        $this->assertSame(3.5, $result['knowing']);
        $this->assertSame(4.2, $result['score']); // 3,5×0,4 + 4,5×0,4 + 5×0,2
        $this->assertSame(Performance::High, $result['performance']);
    }

    public function test_components_without_grades_do_not_count_yet(): void
    {
        $this->grade($this->item('knowing'), $this->andres, 2.0);
        $this->grade($this->item('doing'), $this->andres, 4.0);
        $this->item('being'); // sin notas todavía

        $result = (new ClassGrades($this->seventh, $this->math, $this->period(), GradingScale::forYear($this->year)))->forEnrollment($this->enrollmentId($this->andres));

        $this->assertNull($result['being']);
        $this->assertSame(3.0, $result['score']);
        $this->assertNull((new ClassGrades($this->seventh, $this->math, $this->period(), GradingScale::forYear($this->year)))->forEnrollment($this->enrollmentId($this->sara))['score']);
    }

    public function test_teacher_adds_activities_and_saves_grades_with_comma_decimals(): void
    {
        $this->actingAs($this->teacher)->post(route('portal.teacher.grades.items.store'), [
            'clase' => $this->assignment->id, 'periodo' => $this->period()->id, 'component' => 'doing', 'name' => 'Taller 1',
        ])->assertSessionHasNoErrors();
        $item = GradeItem::firstWhere('name', 'Taller 1');

        $this->actingAs($this->teacher)->put(route('portal.teacher.grades.save'), [
            'clase' => $this->assignment->id, 'periodo' => $this->period()->id,
            'scores' => [$item->id => [$this->enrollmentId($this->andres) => '4,5', $this->enrollmentId($this->sara) => '']],
        ])->assertSessionHasNoErrors()->assertSessionHas('status_message', 'Notas guardadas (1).');

        $this->assertSame(4.5, Score::firstWhere('enrollment_id', $this->enrollmentId($this->andres))->value);
        $this->assertSame(1, Score::count());

        // Vaciar una casilla borra la nota
        $this->actingAs($this->teacher)->put(route('portal.teacher.grades.save'), [
            'clase' => $this->assignment->id, 'periodo' => $this->period()->id,
            'scores' => [$item->id => [$this->enrollmentId($this->andres) => '']],
        ]);
        $this->assertSame(0, Score::count());

        $this->actingAs($this->teacher)->get(route('portal.teacher.grades', ['clase' => $this->assignment->id, 'periodo' => $this->period()->id]))
            ->assertOk()
            ->assertSee('Taller 1')
            ->assertSee('Pérez Díaz, Andrés');
    }

    public function test_grades_outside_the_scale_are_rejected_and_nothing_is_saved(): void
    {
        $item = $this->item('knowing');

        $this->actingAs($this->teacher)->put(route('portal.teacher.grades.save'), [
            'clase' => $this->assignment->id, 'periodo' => $this->period()->id,
            'scores' => [$item->id => [$this->enrollmentId($this->andres) => '4,5', $this->enrollmentId($this->sara) => '5,5']],
        ])->assertSessionHasErrors(['scores.'.$item->id.'.'.$this->enrollmentId($this->sara), 'scores']);

        foreach (['0,5', 'abc', '4.555'] as $bad) {
            $this->actingAs($this->teacher)->put(route('portal.teacher.grades.save'), [
                'clase' => $this->assignment->id, 'periodo' => $this->period()->id,
                'scores' => [$item->id => [$this->enrollmentId($this->andres) => $bad]],
            ])->assertSessionHasErrors('scores');
        }

        $this->assertSame(0, Score::count());
    }

    public function test_teacher_cannot_grade_another_teachers_class_or_a_foreign_student(): void
    {
        $foreign = GradeItem::create(['section_id' => $this->third->id, 'subject_id' => $this->math->id, 'period_id' => $this->period()->id, 'component' => 'knowing', 'name' => 'X']);

        $this->actingAs($this->teacher)->put(route('portal.teacher.grades.save'), [
            'clase' => $this->otherAssignment->id, 'periodo' => $this->period()->id,
            'scores' => [$foreign->id => [$this->enrollmentId($this->lucia) => '1']],
        ])->assertForbidden();
        $this->actingAs($this->teacher)->post(route('portal.teacher.grades.items.store'), [
            'clase' => $this->otherAssignment->id, 'periodo' => $this->period()->id, 'component' => 'doing', 'name' => 'X',
        ])->assertForbidden();
        $this->actingAs($this->teacher)->delete(route('portal.teacher.grades.items.destroy', $foreign))->assertForbidden();

        // Actividad de otra clase o estudiante de otra sección dentro de su propia clase
        $mine = $this->item('knowing');
        $this->actingAs($this->teacher)->put(route('portal.teacher.grades.save'), [
            'clase' => $this->assignment->id, 'periodo' => $this->period()->id,
            'scores' => [$foreign->id => [$this->enrollmentId($this->andres) => '1']],
        ])->assertSessionHasErrors('scores');
        $this->actingAs($this->teacher)->put(route('portal.teacher.grades.save'), [
            'clase' => $this->assignment->id, 'periodo' => $this->period()->id,
            'scores' => [$mine->id => [$this->enrollmentId($this->lucia) => '1']],
        ])->assertSessionHasErrors('scores');

        $this->assertModelExists($foreign);
        $this->assertSame(0, Score::count());
    }

    public function test_closed_period_rejects_every_write(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->item('knowing', 'Evaluación', 2);
        $this->grade($item, $this->andres, 3.0);
        $this->period(2)->close($admin);
        $base = ['clase' => $this->assignment->id, 'periodo' => $this->period(2)->id];

        $this->actingAs($this->teacher)->put(route('portal.teacher.grades.save'), $base + [
            'scores' => [$item->id => [$this->enrollmentId($this->andres) => '5']],
        ])->assertSessionHasErrors('period');
        $this->actingAs($this->teacher)->post(route('portal.teacher.grades.items.store'), $base + ['component' => 'doing', 'name' => 'Nueva'])
            ->assertSessionHasErrors('period');
        $this->actingAs($this->teacher)->delete(route('portal.teacher.grades.items.destroy', $item))->assertSessionHasErrors('period');
        $this->actingAs($this->teacher)->put(route('portal.teacher.grades.objectives'), $base + ['objectives' => ['knowing' => 'resolver']])
            ->assertSessionHasErrors('period');

        $this->assertSame(3.0, Score::first()->value);
        $this->assertSame(1, GradeItem::count());
        $this->assertSame(0, PeriodObjective::count());

        $this->actingAs($this->teacher)->get(route('portal.teacher.grades', $base))
            ->assertOk()
            ->assertSee('sus notas y logros son de solo lectura')
            ->assertDontSee('Guardar notas');
    }

    public function test_closing_freezes_results_and_reopening_clears_them(): void
    {
        $admin = User::factory()->admin()->create();
        $period = $this->period(2);
        $this->grade($this->item('knowing', 'E', 2), $this->andres, 2.0);
        $this->grade($this->item('doing', 'T', 2), $this->andres, 3.0);
        $this->grade($this->item('being', 'P', 2), $this->andres, 4.0);

        $period->close($admin);

        $result = PeriodResult::where('enrollment_id', $this->enrollmentId($this->andres))->where('period_id', $period->id)->first();
        $this->assertSame(3.0, $result->score);
        $this->assertSame(Performance::Basic, $result->performance);
        $this->assertSame(2.0, $result->knowing);
        $this->assertSame(1, PeriodResult::where('enrollment_id', $this->enrollmentId($this->sara))->whereNull('score')->count());

        $period->reopen($admin);
        $this->assertSame(0, PeriodResult::where('period_id', $period->id)->count());
    }

    public function test_teacher_saves_objectives_and_sees_the_phrases(): void
    {
        $this->actingAs($this->teacher)->put(route('portal.teacher.grades.objectives'), [
            'clase' => $this->assignment->id, 'periodo' => $this->period()->id,
            'objectives' => ['knowing' => 'Resolver problemas con fracciones', 'doing' => '', 'being' => 'respetar el turno de la palabra'],
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, PeriodObjective::count());
        $this->assertSame('Soy capaz de resolver problemas con fracciones', GradingScale::forYear($this->year)->objectiveText(Performance::Basic, 'Resolver problemas con fracciones'));

        $this->actingAs($this->teacher)->get(route('portal.teacher.grades', ['clase' => $this->assignment->id, 'periodo' => $this->period()->id]))
            ->assertSee('Demuestro habilidades superiores para')
            ->assertSee('resolver problemas con fracciones');
    }

    public function test_only_the_homeroom_teacher_records_behavior(): void
    {
        $this->seventh->update(['homeroom_teacher_id' => $this->teacher->id]);
        $payload = ['seccion' => $this->seventh->id, 'periodo' => $this->period()->id,
            'behavior' => [$this->enrollmentId($this->andres) => '4,3'],
            'observations' => [$this->enrollmentId($this->andres) => 'Muy participativo.']];

        $this->actingAs($this->otherTeacher)->put(route('portal.teacher.homeroom.save'), $payload)->assertForbidden();
        $this->actingAs($this->otherTeacher)->get(route('portal.teacher.homeroom'))->assertForbidden();

        $this->actingAs($this->teacher)->get(route('portal.teacher.homeroom'))->assertOk()->assertSee('Pérez Díaz, Andrés');
        $this->actingAs($this->teacher)->put(route('portal.teacher.homeroom.save'), $payload)->assertSessionHasNoErrors();

        $report = PeriodReport::first();
        $this->assertSame(4.3, $report->behavior);
        $this->assertSame('Muy participativo.', $report->observations);

        $this->actingAs($this->teacher)->put(route('portal.teacher.homeroom.save'), array_merge($payload, ['behavior' => [$this->enrollmentId($this->andres) => '6']]))
            ->assertSessionHasErrors('behavior.'.$this->enrollmentId($this->andres));

        $this->period()->close(User::factory()->admin()->create());
        $this->actingAs($this->teacher)->put(route('portal.teacher.homeroom.save'), $payload)->assertSessionHasErrors('period');
    }

    public function test_preschool_classes_have_no_numeric_grades(): void
    {
        $preschool = $this->year->sections()->create(['grade_id' => Grade::firstWhere('name', 'Transición')->id, 'name' => '1']);
        $assignment = TeacherAssignment::create(['teacher_id' => $this->teacher->id, 'section_id' => $preschool->id, 'subject_id' => $this->math->id]);

        $this->actingAs($this->teacher)->get(route('portal.teacher.grades', ['clase' => $assignment->id]))
            ->assertOk()->assertSee('Preescolar se evalúa de forma descriptiva');
        $this->actingAs($this->teacher)->post(route('portal.teacher.grades.items.store'), [
            'clase' => $assignment->id, 'periodo' => $this->period()->id, 'component' => 'doing', 'name' => 'X',
        ])->assertForbidden();
    }

    public static function otherRoles(): array
    {
        return ['acudiente' => [Role::Guardian], 'estudiante' => [Role::Student], 'admin' => [Role::Admin]];
    }

    #[DataProvider('otherRoles')]
    public function test_other_roles_get_403_on_grade_routes(Role $role): void
    {
        $user = User::factory()->role($role)->create();
        $item = $this->item('knowing');
        $base = ['clase' => $this->assignment->id, 'periodo' => $this->period()->id];

        $this->actingAs($user)->get(route('portal.teacher.grades'))->assertForbidden();
        $this->actingAs($user)->put(route('portal.teacher.grades.save'), $base + ['scores' => [$item->id => [$this->enrollmentId($this->andres) => '5']]])->assertForbidden();
        $this->actingAs($user)->post(route('portal.teacher.grades.items.store'), $base + ['component' => 'doing', 'name' => 'X'])->assertForbidden();
        $this->actingAs($user)->delete(route('portal.teacher.grades.items.destroy', $item))->assertForbidden();
        $this->actingAs($user)->put(route('portal.teacher.grades.objectives'), $base + ['objectives' => ['knowing' => 'x']])->assertForbidden();
        $this->actingAs($user)->get(route('portal.teacher.homeroom'))->assertForbidden();
        $this->actingAs($user)->put(route('portal.teacher.homeroom.save'), ['seccion' => $this->seventh->id, 'periodo' => $this->period()->id])->assertForbidden();

        $this->assertSame(0, Score::count());
        $this->assertModelExists($item);
    }

    public function test_admin_edits_the_grading_scale_with_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $valid = [
            'min_score' => '1', 'max_score' => '5', 'passing_score' => '3,5', 'decimals' => 1,
            'basic_from' => '3,5', 'high_from' => '4', 'superior_from' => '4,6',
            'phrases' => ['low' => 'Aún necesito', 'basic' => 'Soy capaz de', 'high' => 'Tengo buenas habilidades para', 'superior' => 'Demuestro habilidades superiores para'],
            'weight_knowing' => '40', 'weight_doing' => '40', 'weight_being' => '20',
        ];

        $this->actingAs($admin)->get(route('admin.academic.scale.edit', $this->year))->assertOk()->assertSee('Escala de valoración 2026');
        $this->actingAs($admin)->put(route('admin.academic.scale.update', $this->year), $valid)->assertSessionHasNoErrors();

        $scale = GradingScale::forYear($this->year);
        $this->assertSame(3.5, $scale->passing_score);
        $this->assertSame(Performance::High, $scale->performanceFor(4.0));
        $this->assertSame('Aún necesito', $scale->phrase(Performance::Low));

        $this->actingAs($admin)->put(route('admin.academic.scale.update', $this->year), array_merge($valid, ['weight_being' => '30']))
            ->assertSessionHasErrors('weights');
        $this->actingAs($admin)->put(route('admin.academic.scale.update', $this->year), array_merge($valid, ['high_from' => '3']))
            ->assertSessionHasErrors('high_from');

        foreach ([$this->teacher, User::factory()->role(Role::Guardian)->create()] as $user) {
            $this->actingAs($user)->get(route('admin.academic.scale.edit', $this->year))->assertForbidden();
            $this->actingAs($user)->put(route('admin.academic.scale.update', $this->year), $valid)->assertForbidden();
        }
    }

    public function test_evaluation_components_have_spanish_labels(): void
    {
        $this->assertSame(['Saber', 'Hacer', 'Ser'], array_map(fn ($c) => $c->label(), EvaluationComponent::cases()));
    }
}
