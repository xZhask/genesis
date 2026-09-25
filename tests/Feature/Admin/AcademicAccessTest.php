<?php

namespace Tests\Feature\Admin;

use App\Enums\PeriodStatus;
use App\Enums\Role;
use App\Models\Area;
use App\Models\Grade;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Solo el admin gestiona la estructura académica. En particular, nadie más
 * puede cerrar ni reabrir un periodo (reglas de seguridad 2 y 3).
 */
class AcademicAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function nonAdminRoles(): array
    {
        return [
            'docente' => [Role::Teacher],
            'estudiante' => [Role::Student],
            'acudiente' => [Role::Guardian],
        ];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_roles_get_403(Role $role): void
    {
        $year = SchoolYear::create(['year' => 2027, 'starts_on' => '2027-01-25', 'ends_on' => '2027-11-26']);
        $year->createPeriods(4);
        $period = $year->periods->first();
        $period->close(User::factory()->admin()->create());
        $grade = Grade::first();
        $section = $year->sections()->create(['grade_id' => $grade->id, 'name' => '1']);
        $area = Area::create(['name' => 'Humanidades']);
        $subject = Subject::create(['name' => 'Español', 'area_id' => $area->id]);
        $user = User::factory()->role($role)->create();

        $requests = [
            ['GET', route('admin.academic.years.index')],
            ['POST', route('admin.academic.years.store')],
            ['GET', route('admin.academic.years.edit', $year)],
            ['PUT', route('admin.academic.years.update', $year)],
            ['POST', route('admin.academic.years.current', $year)],
            ['PUT', route('admin.academic.periods.update', $year)],
            ['POST', route('admin.academic.periods.close', $year->periods[1])],
            ['POST', route('admin.academic.periods.reopen', $period)],
            ['GET', route('admin.academic.sections.index')],
            ['POST', route('admin.academic.sections.store', $year)],
            ['POST', route('admin.academic.sections.defaults', $year)],
            ['DELETE', route('admin.academic.sections.destroy', $section)],
            ['GET', route('admin.academic.subjects.index')],
            ['POST', route('admin.academic.subjects.store')],
            ['PUT', route('admin.academic.subjects.update', $subject)],
            ['DELETE', route('admin.academic.subjects.destroy', $subject)],
            ['POST', route('admin.academic.areas.store')],
            ['DELETE', route('admin.academic.areas.destroy', $area)],
            ['GET', route('admin.academic.curriculum.edit', $grade)],
            ['PUT', route('admin.academic.curriculum.update', $grade)],
            ['POST', route('admin.academic.curriculum.copy', $grade)],
        ];

        foreach ($requests as [$method, $url]) {
            $this->actingAs($user)->call($method, $url, ['name' => 'X', 'year' => 2030])->assertForbidden();
        }

        $this->assertSame(PeriodStatus::Closed, $period->fresh()->status, 'El periodo sigue cerrado');
        $this->assertSame(PeriodStatus::Open, $year->periods[1]->fresh()->status);
        $this->assertModelExists($section);
        $this->assertModelExists($subject);
        $this->assertSame(1, SchoolYear::count());
    }
}
