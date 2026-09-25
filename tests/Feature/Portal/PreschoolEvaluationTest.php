<?php

namespace Tests\Feature\Portal;

use App\Enums\Role;
use App\Models\DescriptiveEvaluation;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Support\PreschoolReportCard;

/** Preescolar: evaluación descriptiva por dimensión, sin notas (provisional). */
class PreschoolEvaluationTest extends PortalTestCase
{
    private Section $garden;

    private Subject $dimension;

    private TeacherAssignment $gardenClass;

    private Student $sofia;

    protected function setUp(): void
    {
        parent::setUp();

        $grade = Grade::firstWhere('name', 'Jardín');
        $this->dimension = Subject::create(['name' => 'Dimensión comunicativa', 'area_id' => $this->math->area_id]);
        $grade->subjects()->attach($this->dimension->id, ['weekly_hours' => 0]);
        $this->garden = $this->year->sections()->create(['grade_id' => $grade->id, 'name' => '1', 'homeroom_teacher_id' => $this->teacher->id]);
        $this->gardenClass = TeacherAssignment::create(['teacher_id' => $this->teacher->id, 'section_id' => $this->garden->id, 'subject_id' => $this->dimension->id]);
        $this->sofia = Student::factory()->create(['first_names' => 'Sofía', 'last_names' => 'Mercado Ruiz']);
        Enrollment::place($this->sofia, $this->garden);
    }

    private function save(User $user, array $descriptions, int $period = 3)
    {
        return $this->actingAs($user)->put(route('portal.teacher.grades.descriptions'), [
            'clase' => $this->gardenClass->id, 'periodo' => $this->year->periods[$period]->id, 'descriptions' => $descriptions,
        ]);
    }

    public function test_teacher_writes_descriptions_instead_of_grades(): void
    {
        $this->actingAs($this->teacher)->get(route('portal.teacher.grades', ['clase' => $this->gardenClass->id]))
            ->assertOk()
            ->assertSee('Preescolar se evalúa de forma descriptiva')
            ->assertSee('Mercado Ruiz, Sofía')
            ->assertDontSee('Planilla');

        $this->save($this->teacher, [$this->enrollmentId($this->sofia) => 'Expresa sus ideas con frases completas.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Expresa sus ideas con frases completas.', DescriptiveEvaluation::first()->text);

        $this->save($this->teacher, [$this->enrollmentId($this->sofia) => '  ']);
        $this->assertSame(0, DescriptiveEvaluation::count());
    }

    public function test_closed_period_and_other_teachers_cannot_write(): void
    {
        $this->year->periods[2]->close(User::factory()->admin()->create());
        $this->save($this->teacher, [$this->enrollmentId($this->sofia) => 'Texto'], 2)->assertSessionHasErrors('period');

        $this->save($this->otherTeacher, [$this->enrollmentId($this->sofia) => 'Texto'])->assertForbidden();
        $this->save(User::factory()->role(Role::Guardian)->create(), [$this->enrollmentId($this->sofia) => 'Texto'])->assertForbidden();

        // Un niño de otra sección
        $this->save($this->teacher, [$this->enrollmentId($this->andres) => 'Texto'])->assertSessionHasErrors('period');

        $this->assertSame(0, DescriptiveEvaluation::count());
    }

    public function test_descriptions_are_only_for_preschool(): void
    {
        $this->actingAs($this->teacher)->put(route('portal.teacher.grades.descriptions'), [
            'clase' => $this->assignment->id, 'periodo' => $this->year->periods[3]->id,
            'descriptions' => [$this->enrollmentId($this->andres) => 'Texto'],
        ])->assertForbidden();
    }

    public function test_family_sees_descriptions_after_closing_and_downloads_the_report(): void
    {
        $this->save($this->teacher, [$this->enrollmentId($this->sofia) => 'Disfruta escuchar cuentos.'], 0);
        $guardian = $this->guardianOf($this->sofia);

        $this->actingAs($guardian)->get(route('portal.guardian.student', $this->sofia))
            ->assertOk()->assertSee('El informe aparece aquí cuando el colegio cierra')->assertDontSee('Disfruta escuchar cuentos.');

        $this->year->periods[0]->close(User::factory()->admin()->create());

        $this->actingAs($guardian)->get(route('portal.guardian.student', $this->sofia))
            ->assertSee('Dimensión comunicativa')
            ->assertSee('Disfruta escuchar cuentos.')
            ->assertSee('Informe P1')
            ->assertDontSee('Acum.');

        $this->actingAs($guardian)->get(route('report-cards.student', [$this->sofia, $this->year->periods[0]]))
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $card = new PreschoolReportCard($this->sofia->enrollmentFor($this->year), $this->year->periods[0]->fresh());
        $html = view('pdf.preschool-report', ['cards' => [$card], 'school' => config('school')])->render();
        $this->assertStringContainsString('Disfruta escuchar cuentos.', $html);
        $this->assertStringContainsString('Mercado Ruiz, Sofía', $html);
        $this->assertStringNotContainsString('Promedio', $html);
    }

    public function test_progress_counts_preschool_descriptions(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.academic.periods.progress', $this->year->periods[3]))
            ->assertOk()
            ->assertSee('Jardín 1')
            ->assertSee('0 de 1 descripciones');

        $this->save($this->teacher, [$this->enrollmentId($this->sofia) => 'Texto']);

        $this->actingAs($admin)->get(route('admin.academic.periods.progress', [$this->year->periods[3], 'ver' => 'todas']))
            ->assertSee('1 de 1 descripciones');
    }
}
