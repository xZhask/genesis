<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Datos de estudiantes y acudientes, cuentas e importación: solo el admin
 * (regla de seguridad 2, Ley 1581). Cualquier otro rol recibe 403 aunque
 * escriba la URL a mano, y nada cambia.
 */
class PeopleAccessTest extends TestCase
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
        $year->forceFill(['is_current' => true])->save();
        $student = Student::factory()->create();
        $guardian = Guardian::factory()->create();
        $student->guardians()->attach($guardian->id, ['relationship' => 'mother', 'is_primary' => true]);
        $teacher = User::factory()->role(Role::Teacher)->create();
        $user = User::factory()->role($role)->create();

        $requests = [
            ['GET', route('admin.people.students.index')],
            ['GET', route('admin.people.students.create')],
            ['POST', route('admin.people.students.store')],
            ['GET', route('admin.people.students.edit', $student)],
            ['PUT', route('admin.people.students.update', $student)],
            ['DELETE', route('admin.people.students.destroy', $student)],
            ['PUT', route('admin.people.students.enroll', $student)],
            ['POST', route('admin.people.students.guardians.store', $student)],
            ['PUT', route('admin.people.students.guardians.update', [$student, $guardian])],
            ['DELETE', route('admin.people.students.guardians.destroy', [$student, $guardian])],
            ['GET', route('admin.people.guardians.index')],
            ['GET', route('admin.people.guardians.edit', $guardian)],
            ['PUT', route('admin.people.guardians.update', $guardian)],
            ['DELETE', route('admin.people.guardians.destroy', $guardian)],
            ['GET', route('admin.people.staff.index')],
            ['GET', route('admin.people.staff.create')],
            ['POST', route('admin.people.staff.store')],
            ['GET', route('admin.people.staff.edit', $teacher)],
            ['PUT', route('admin.people.staff.update', $teacher)],
            ['POST', route('admin.people.accounts.student', $student)],
            ['POST', route('admin.people.accounts.guardian', $guardian)],
            ['POST', route('admin.people.accounts.reset', $teacher)],
            ['POST', route('admin.people.accounts.toggle', $teacher)],
            ['POST', route('admin.people.accounts.bulk')],
            ['GET', route('admin.people.import.create')],
            ['GET', route('admin.people.import.template')],
            ['POST', route('admin.people.import.preview')],
            ['POST', route('admin.people.import.store')],
            ['DELETE', route('admin.people.import.discard')],
        ];

        $payload = ['first_names' => 'X', 'name' => 'X', 'role' => 'admin', 'type' => 'guardians', 'relationship' => 'father'];
        foreach ($requests as [$method, $url]) {
            $this->actingAs($user)->call($method, $url, $payload)->assertForbidden();
        }

        $this->assertModelExists($student);
        $this->assertModelExists($guardian);
        $this->assertSame(1, $student->guardians()->count());
        $this->assertNull($guardian->fresh()->user_id);
        $this->assertSame(Role::Teacher, $teacher->fresh()->role);
        $this->assertTrue($teacher->fresh()->is_active);
        $this->assertSame(2, User::count());
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('admin.people.students.index'))->assertRedirect(route('login'));
        $this->get(route('admin.people.import.template'))->assertRedirect(route('login'));
    }
}
