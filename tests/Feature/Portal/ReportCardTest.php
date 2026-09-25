<?php

namespace Tests\Feature\Portal;

use App\Enums\Role;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\PeriodObjective;
use App\Models\PeriodReport;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Support\ReportCard;

/** Boletín en PDF: quién lo descarga, solo de periodos cerrados, y su contenido. */
class ReportCardTest extends PortalTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        // Periodo 1 con notas y cerrado
        foreach (['knowing' => 4.0, 'doing' => 3.0, 'being' => 5.0] as $component => $value) {
            $item = GradeItem::create(['section_id' => $this->seventh->id, 'subject_id' => $this->math->id,
                'period_id' => $this->year->periods[0]->id, 'component' => $component, 'name' => $component]);
            Score::create(['grade_item_id' => $item->id, 'enrollment_id' => $this->enrollmentId($this->andres), 'value' => $value]);
            PeriodObjective::create(['section_id' => $this->seventh->id, 'subject_id' => $this->math->id,
                'period_id' => $this->year->periods[0]->id, 'component' => $component, 'text' => "Logro de {$component}"]);
        }
        PeriodReport::create(['enrollment_id' => $this->enrollmentId($this->andres), 'period_id' => $this->year->periods[0]->id,
            'behavior' => 4.6, 'observations' => 'Muy buen compañero.']);
        $this->year->periods[0]->close($this->admin);
    }

    private function url($student = null, int $period = 0): string
    {
        return route('report-cards.student', [$student ?? $this->andres, $this->year->periods[$period]]);
    }

    public function test_family_and_homeroom_teacher_download_the_pdf(): void
    {
        $this->seventh->update(['homeroom_teacher_id' => $this->teacher->id]);
        $student = User::factory()->role(Role::Student)->create();
        $this->andres->user()->associate($student)->save();

        foreach ([$this->guardianOf($this->andres), $student, $this->teacher, $this->admin] as $user) {
            $response = $this->actingAs($user)->get($this->url());
            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }
    }

    public function test_others_get_403(): void
    {
        $otherStudent = User::factory()->role(Role::Student)->create();
        $this->sara->user()->associate($otherStudent)->save();

        // Otra familia, otro estudiante y un docente que no es el director de grupo
        foreach ([$this->guardianOf($this->lucia), $otherStudent, $this->teacher, $this->otherTeacher] as $user) {
            $this->actingAs($user)->get($this->url())->assertForbidden();
        }
    }

    public function test_only_closed_periods_have_a_report_card(): void
    {
        $guardian = $this->guardianOf($this->andres);

        $this->actingAs($guardian)->get($this->url(period: 3))->assertNotFound();

        $this->year->periods[0]->reopen($this->admin);
        $this->actingAs($guardian)->get($this->url())->assertNotFound();
    }

    public function test_group_pdf_for_homeroom_teacher_and_admin_only(): void
    {
        $this->seventh->update(['homeroom_teacher_id' => $this->teacher->id]);
        $url = route('report-cards.section', [$this->seventh, $this->year->periods[0]]);

        $this->actingAs($this->teacher)->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->admin)->get($url)->assertOk();
        $this->actingAs($this->otherTeacher)->get($url)->assertForbidden();
        $this->actingAs($this->guardianOf($this->andres))->get($url)->assertForbidden();
        $this->actingAs($this->teacher)->get(route('report-cards.section', [$this->seventh, $this->year->periods[1]]))->assertNotFound();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get($this->url())->assertRedirect(route('login'));
    }

    public function test_content_areas_objectives_and_behavior(): void
    {
        // Segunda materia del área para ver la nota del área
        $stats = Subject::create(['name' => 'Estadística', 'area_id' => $this->math->area_id]);
        Grade::firstWhere('name', '7.°')->subjects()->attach($stats->id, ['weekly_hours' => 1]);

        $card = new ReportCard($this->andres->enrollmentFor($this->year), $this->year->periods[0]->fresh());
        $math = $card->areas()->first()['subjects']->firstWhere('name', 'Matemáticas');

        $this->assertSame(4.0, $math['score']);                     // (4 + 3 + 5) / 3
        $this->assertSame(1.0, $math['accumulated']);              // 4 × 25 %
        $this->assertSame('needs', $math['status']['key']);
        $this->assertSame(2.67, $math['status']['needed']);         // (3 − 1) / 0,75
        $this->assertSame([
            'Soy capaz de logro de knowing',
            'Soy capaz de logro de doing',
            'Demuestro habilidades superiores para logro de being',
        ], $math['objectives']);
        $this->assertFalse($card->isFinal());

        $html = view('pdf.report-card', ['cards' => [$card], 'school' => config('school')])->render();
        $this->assertStringContainsString('Pérez Díaz, Andrés', $html);
        $this->assertStringContainsString('resolución No. 3332', $html);
        $this->assertStringContainsString('NIT 1102821784-1', $html);
        $this->assertStringContainsString('Muy buen compañero.', $html);
        $this->assertStringContainsString('4,60', $html);
        $this->assertStringContainsString('Rectoría', $html);
        $this->assertStringNotContainsString('Puesto', $html);
    }

    public function test_preschool_has_no_numeric_report_card_yet(): void
    {
        $preschool = $this->year->sections()->create(['grade_id' => Grade::firstWhere('name', 'Jardín')->id, 'name' => '1']);
        $child = Student::factory()->create();
        Enrollment::place($child, $preschool);

        $this->actingAs($this->admin)->get(route('report-cards.student', [$child, $this->year->periods[0]]))->assertNotFound();
        $this->actingAs($this->admin)->get(route('report-cards.section', [$preschool, $this->year->periods[0]]))->assertNotFound();
    }
}
