<?php

namespace Tests\Feature\Admin;

use App\Enums\PeriodStatus;
use App\Models\Grade;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\AcademicCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicStructureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    private function createYear(array $overrides = []): SchoolYear
    {
        $this->actingAs($this->admin)->post(route('admin.academic.years.store'), [
            'year' => 2027,
            'starts_on' => '2027-01-25',
            'ends_on' => '2027-11-26',
            ...$overrides,
        ])->assertSessionHasNoErrors();

        return SchoolYear::where('year', $overrides['year'] ?? 2027)->sole();
    }

    public function test_the_thirteen_grades_exist_in_order_without_upper_secondary(): void
    {
        $this->assertSame(
            ['Párvulos', 'Prejardín', 'Jardín', 'Transición', '1.°', '2.°', '3.°', '4.°', '5.°', '6.°', '7.°', '8.°', '9.°'],
            Grade::ordered()->pluck('name')->all(),
        );
        $this->assertSame(['preschool', 'primary', 'secondary'], Grade::ordered()->pluck('level')->unique()->values()->all());
    }

    public function test_catalog_seeder_builds_a_provisional_plan_and_never_overwrites(): void
    {
        $this->seed(AcademicCatalogSeeder::class);

        $third = Grade::where('name', '3.°')->sole();
        $this->assertSame(5, $third->subjects->firstWhere('name', 'Español')->pivot->weekly_hours);
        $this->assertSame(7, Grade::where('name', 'Jardín')->sole()->subjects()->count(), 'Preescolar: 7 dimensiones');
        $this->assertTrue(Grade::where('name', '8.°')->sole()->subjects->contains('name', 'Álgebra'));
        $this->assertFalse(Grade::where('name', '8.°')->sole()->subjects->contains('name', 'Matemáticas'));

        // El admin cambia el plan de 3.°; volver a ejecutar el seeder no lo toca
        $third->subjects()->sync([Subject::where('name', 'Español')->value('id') => ['weekly_hours' => 6]]);
        $this->seed(AcademicCatalogSeeder::class);

        $this->assertSame(1, $third->subjects()->count());
        $this->assertSame(26, Subject::count());
    }

    public function test_creating_a_year_creates_four_equal_periods_covering_it(): void
    {
        $year = $this->createYear();

        $periods = $year->periods;
        $this->assertCount(4, $periods);
        $this->assertSame([25.0, 25.0, 25.0, 25.0], $periods->map(fn ($p) => (float) $p->weight)->all());
        $this->assertTrue($periods->first()->starts_on->equalTo($year->starts_on));
        $this->assertTrue($periods->last()->ends_on->equalTo($year->ends_on));

        foreach ($periods->values() as $i => $period) {
            if ($i > 0) {
                $this->assertTrue($period->starts_on->equalTo($periods[$i - 1]->ends_on->copy()->addDay()), 'Periodos seguidos, sin huecos');
            }
        }

        $this->assertTrue($year->is_current, 'El primer año queda como actual');
    }

    public function test_only_one_current_year(): void
    {
        $first = $this->createYear();
        $second = $this->createYear(['year' => 2028, 'starts_on' => '2028-01-24', 'ends_on' => '2028-11-24']);

        $this->assertFalse($second->is_current);

        $this->actingAs($this->admin)->post(route('admin.academic.years.current', $second));

        $this->assertFalse($first->fresh()->is_current);
        $this->assertTrue($second->fresh()->is_current);
        $this->assertSame($second->id, SchoolYear::current()->id);
    }

    public function test_period_dates_and_weights_are_validated(): void
    {
        $year = $this->createYear();
        $periods = fn (array $weights, array $dates) => collect($weights)->map(fn ($w, $i) => [
            'starts_on' => $dates[$i][0], 'ends_on' => $dates[$i][1], 'weight' => $w,
        ])->all();
        $dates = [['2027-01-25', '2027-04-02'], ['2027-04-05', '2027-06-18'], ['2027-07-06', '2027-09-10'], ['2027-09-13', '2027-11-26']];

        $this->actingAs($this->admin)
            ->put(route('admin.academic.periods.update', $year), ['periods' => $periods([30, 30, 30, 30], $dates)])
            ->assertSessionHasErrors(['periods' => 'Los pesos deben sumar 100 % (ahora suman 120 %).']);

        $overlap = $dates;
        $overlap[1][0] = '2027-03-30';
        $this->actingAs($this->admin)
            ->put(route('admin.academic.periods.update', $year), ['periods' => $periods([25, 25, 25, 25], $overlap)])
            ->assertSessionHasErrors('periods');

        $this->actingAs($this->admin)
            ->put(route('admin.academic.periods.update', $year), ['periods' => $periods([20, 30, 20, 30], $dates)])
            ->assertSessionHasNoErrors();

        $this->assertSame('2027-07-06', $year->periods()->where('number', 3)->value('starts_on')->toDateString());
        $this->assertEquals(30, $year->periods()->where('number', 2)->value('weight'));
    }

    public function test_closing_and_reopening_a_period_is_recorded(): void
    {
        $period = $this->createYear()->periods->first();

        $this->actingAs($this->admin)->post(route('admin.academic.periods.close', $period));
        $period->refresh();
        $this->assertSame(PeriodStatus::Closed, $period->status);
        $this->assertSame($this->admin->id, $period->closed_by);
        $this->assertNotNull($period->closed_at);

        $this->actingAs($this->admin)->post(route('admin.academic.periods.reopen', $period));
        $period->refresh();
        $this->assertSame(PeriodStatus::Open, $period->status);
        $this->assertNull($period->closed_by);

        $this->assertSame(['open', 'closed'], $period->statusChanges()->pluck('status')->map->value->all());
        $this->assertSame([$this->admin->id], $period->statusChanges()->pluck('user_id')->unique()->all());

        $this->actingAs($this->admin)->get(route('admin.academic.years.edit', $period->schoolYear))
            ->assertSee('Reabierto el')
            ->assertSee('Cerrado el');
    }

    public function test_sections_are_unique_per_grade_and_year(): void
    {
        $year = $this->createYear();
        $third = Grade::where('name', '3.°')->sole();

        $this->actingAs($this->admin)->post(route('admin.academic.sections.store', $year), ['grade_id' => $third->id, 'name' => '1']);
        $this->actingAs($this->admin)->post(route('admin.academic.sections.store', $year), ['grade_id' => $third->id, 'name' => '2']);
        $this->actingAs($this->admin)
            ->post(route('admin.academic.sections.store', $year), ['grade_id' => $third->id, 'name' => '2'])
            ->assertSessionHasErrors(['name' => 'Ese grado ya tiene una sección con ese nombre.']);
        $this->actingAs($this->admin)
            ->post(route('admin.academic.sections.store', $year), ['grade_id' => $third->id, 'name' => '3 B'])
            ->assertSessionHasErrors('name');

        $this->assertSame(['3.° 1', '3.° 2'], Section::with('grade')->get()->map->label()->all());

        $this->actingAs($this->admin)->post(route('admin.academic.sections.defaults', $year));
        $this->assertSame(14, $year->sections()->count(), '12 grados sin sección + las 2 de 3.°');
    }

    public function test_subjects_and_curriculum(): void
    {
        $this->seed(AcademicCatalogSeeder::class);
        $area = Subject::where('name', 'Español')->sole()->area;
        $grade = Grade::where('name', '4.°')->sole();

        $this->actingAs($this->admin)->post(route('admin.academic.subjects.store'), ['name' => 'Lectura crítica', 'area_id' => $area->id])
            ->assertSessionHasNoErrors();
        $reading = Subject::where('name', 'Lectura crítica')->sole();

        // No se elimina una materia que está en un plan de estudios
        $spanish = Subject::where('name', 'Español')->sole();
        $this->actingAs($this->admin)->delete(route('admin.academic.subjects.destroy', $spanish))->assertSessionHasErrors('subject');
        $this->assertModelExists($spanish);

        $this->actingAs($this->admin)->put(route('admin.academic.curriculum.update', $grade), ['subjects' => [
            $spanish->id => ['included' => '1', 'hours' => '6'],
            $reading->id => ['included' => '1', 'hours' => '2'],
            Subject::where('name', 'Música')->value('id') => ['included' => '0', 'hours' => '1'],
        ]])->assertSessionHas('status_message', 'Se guardó el plan de estudios de 4.°: 2 materias, 8 horas semanales.');

        $this->assertEqualsCanonicalizing(['Español', 'Lectura crítica'], $grade->subjects()->pluck('name')->all());

        $fifth = Grade::where('name', '5.°')->sole();
        $this->actingAs($this->admin)->post(route('admin.academic.curriculum.copy', $fifth), ['from' => $grade->id]);
        $this->assertSame(6, $fifth->subjects()->where('name', 'Español')->first()->pivot->weekly_hours);
    }

    public function test_admin_pages_render(): void
    {
        $this->seed(AcademicCatalogSeeder::class);
        $year = $this->createYear();

        foreach ([
            route('admin.academic.years.index'),
            route('admin.academic.years.edit', $year),
            route('admin.academic.sections.index'),
            route('admin.academic.subjects.index'),
            route('admin.academic.curriculum.edit', Grade::first()),
        ] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }

        $this->actingAs($this->admin)->get(route('admin.academic.curriculum.index'))
            ->assertRedirect(route('admin.academic.curriculum.edit', Grade::orderBy('position')->first()));
    }
}
