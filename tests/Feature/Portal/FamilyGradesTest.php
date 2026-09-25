<?php

namespace Tests\Feature\Portal;

use App\Enums\Role;
use App\Models\GradeItem;
use App\Models\GradingScale;
use App\Models\PeriodObjective;
use App\Models\PeriodReport;
use App\Models\Score;
use App\Models\User;
use App\Support\StudentOverview;

/**
 * Notas en el portal del acudiente y del estudiante: solo de periodos
 * cerrados, con acumulado, lo que falta para aprobar y logros con la frase
 * del desempeño. Y el avance de notas del admin antes de cerrar.
 */
class FamilyGradesTest extends PortalTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    /** Una actividad por componente con la misma nota para el estudiante. */
    private function gradePeriod(int $index, float $knowing, float $doing, float $being, $student = null): void
    {
        $student ??= $this->andres;
        foreach (['knowing' => $knowing, 'doing' => $doing, 'being' => $being] as $component => $value) {
            $item = GradeItem::firstOrCreate([
                'section_id' => $this->seventh->id, 'subject_id' => $this->math->id,
                'period_id' => $this->year->periods[$index]->id, 'component' => $component,
            ], ['name' => ucfirst($component)]);
            Score::create(['grade_item_id' => $item->id, 'enrollment_id' => $this->enrollmentId($student), 'value' => $value]);
        }
    }

    private function objectives(int $index): void
    {
        foreach (['knowing' => 'Resolver problemas con fracciones', 'doing' => 'representar datos en tablas', 'being' => 'respetar el turno de la palabra'] as $component => $text) {
            PeriodObjective::create([
                'section_id' => $this->seventh->id, 'subject_id' => $this->math->id,
                'period_id' => $this->year->periods[$index]->id, 'component' => $component, 'text' => $text,
            ]);
        }
    }

    public function test_families_see_only_closed_periods_with_accumulated_and_objectives(): void
    {
        $guardian = $this->guardianOf($this->andres);
        $this->gradePeriod(0, 3.0, 3.0, 3.0);   // P1 = 3,00
        $this->gradePeriod(1, 5.0, 4.0, 4.0);   // P2 = 4,33 (componente saber Superior)
        $this->objectives(1);
        $this->gradePeriod(3, 1.0, 1.0, 1.0);   // P4 abierto: no se muestra
        PeriodReport::create(['enrollment_id' => $this->enrollmentId($this->andres), 'period_id' => $this->year->periods[1]->id, 'behavior' => 4.5, 'observations' => 'Muy participativo.']);

        $this->year->periods[0]->close($this->admin);
        $this->year->periods[1]->close($this->admin);

        $response = $this->actingAs($guardian)->get(route('portal.guardian.student', $this->andres))->assertOk();

        $response->assertSee('Notas')
            ->assertSee('3,00')
            ->assertSee('4,33')
            ->assertDontSee('1,00')
            ->assertSee('1,83') // acumulado: 3×0,25 + 4,33×0,25
            ->assertSee('Necesita en promedio 2,34 en los periodos que faltan')
            ->assertSee('Logros del periodo 2')
            ->assertSee('Demuestro habilidades superiores para resolver problemas con fracciones')
            ->assertSee('Soy capaz de representar datos en tablas')
            ->assertSee('Muy participativo.');
    }

    public function test_nothing_is_shown_before_the_first_period_closes(): void
    {
        $this->gradePeriod(0, 4.0, 4.0, 4.0);
        $user = User::factory()->role(Role::Student)->create();
        $this->andres->user()->associate($user)->save();

        $this->actingAs($user)->get(route('portal.student.home'))
            ->assertOk()
            ->assertSee('Las notas aparecen aquí cuando el colegio cierra cada periodo')
            ->assertDontSee('4,00');
    }

    public function test_status_when_the_year_is_already_passed_or_out_of_reach(): void
    {
        foreach ([0, 1, 2] as $i) {
            $this->gradePeriod($i, 5.0, 5.0, 5.0);
            $this->gradePeriod($i, 1.0, 1.0, 1.0, $this->sara);
            $this->year->periods[$i]->close($this->admin);
        }

        $andres = (new StudentOverview($this->andres, $this->year->fresh()))->grades()->first();
        $this->assertSame(3.75, $andres['accumulated']);
        $this->assertSame('reached', $andres['status']['key']);

        // Sara lleva 0,75: necesitaría 9,0 en el último periodo
        $sara = (new StudentOverview($this->sara, $this->year->fresh()))->grades()->first();
        $this->assertSame('support', $sara['status']['key']);
    }

    public function test_reopening_a_period_hides_its_grades_again(): void
    {
        $guardian = $this->guardianOf($this->andres);
        $this->gradePeriod(0, 4.1, 4.1, 4.1);
        $this->year->periods[0]->close($this->admin);
        $this->actingAs($guardian)->get(route('portal.guardian.student', $this->andres))->assertSee('4,10');

        $this->year->periods[0]->reopen($this->admin);
        $this->actingAs($guardian)->get(route('portal.guardian.student', $this->andres))->assertDontSee('4,10');
    }

    public function test_another_family_cannot_see_the_grades(): void
    {
        $this->gradePeriod(0, 4.1, 4.1, 4.1);
        $this->year->periods[0]->close($this->admin);
        $guardian = $this->guardianOf($this->lucia);

        $this->actingAs($guardian)->get(route('portal.guardian.student', $this->andres))->assertForbidden();
    }

    public function test_admin_sees_grading_progress_before_closing(): void
    {
        $period = $this->year->periods[3];
        $this->gradePeriod(3, 4.0, 4.0, 4.0); // Andrés sí, Sara no

        $this->actingAs($this->admin)->get(route('admin.academic.periods.progress', $period))
            ->assertOk()
            ->assertSee('0 de 2')        // clases completas (7.° y 3.°)
            ->assertSee('3 de 6')        // notas de 7.° 1: 3 actividades × 2 estudiantes
            ->assertSee('0 de 3')        // logros
            ->assertSee('Cerrar periodo');

        $this->gradePeriod(3, 4.0, 4.0, 4.0, $this->sara);
        $this->objectives(3);
        foreach (['knowing', 'doing', 'being'] as $component) {
            $item = GradeItem::create(['section_id' => $this->third->id, 'subject_id' => $this->math->id, 'period_id' => $period->id, 'component' => $component, 'name' => 'X']);
            Score::create(['grade_item_id' => $item->id, 'enrollment_id' => $this->enrollmentId($this->lucia), 'value' => 4]);
            PeriodObjective::create(['section_id' => $this->third->id, 'subject_id' => $this->math->id, 'period_id' => $period->id, 'component' => $component, 'text' => 'x']);
        }

        $this->actingAs($this->admin)->get(route('admin.academic.periods.progress', $period))
            ->assertSee('2 de 2')
            ->assertSee('estudiantes sin comportamiento');
    }

    public function test_only_admin_sees_the_progress_page(): void
    {
        foreach ([$this->teacher, User::factory()->role(Role::Guardian)->create(), User::factory()->role(Role::Student)->create()] as $user) {
            $this->actingAs($user)->get(route('admin.academic.periods.progress', $this->year->periods[3]))->assertForbidden();
        }
    }

    public function test_the_scale_formats_with_decimal_comma(): void
    {
        $this->assertSame('4,33', GradingScale::forYear($this->year)->format(4.333));
    }
}
