<?php

namespace Tests\Feature\Admin;

use App\Enums\EnrollmentStatus;
use App\Enums\GuardianRelationship;
use App\Enums\Role;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PeopleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private SchoolYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
        $this->year = SchoolYear::create(['year' => 2027, 'starts_on' => '2027-01-25', 'ends_on' => '2027-11-26']);
        $this->year->makeCurrent();
    }

    private function section(string $grade, string $name = '1'): Section
    {
        return $this->year->sections()->firstOrCreate(['grade_id' => Grade::firstWhere('name', $grade)->id, 'name' => $name]);
    }

    private function enrolledStudent(string $grade = '3.°', array $attributes = []): Student
    {
        $student = Student::factory()->create($attributes);
        Enrollment::place($student, $this->section($grade));

        return $student;
    }

    public function test_admin_creates_a_student_and_enrolls_them(): void
    {
        $section = $this->section('3.°', '2');

        $response = $this->actingAs($this->admin)->post(route('admin.people.students.store'), [
            'document_type' => 'TI',
            'document_number' => '1.047.123.456',
            'first_names' => 'Valentina',
            'last_names' => 'Barrios Julio',
            'birth_date' => '2018-03-14',
            'section_id' => $section->id,
        ]);

        $student = Student::firstWhere('document_number', '1047123456');
        $response->assertRedirect(route('admin.people.students.edit', $student));
        $this->assertSame('3.° 2', $student->enrollmentFor($this->year)->section->label());
        $this->assertSame(EnrollmentStatus::Active, $student->enrollmentFor($this->year)->status);

        $this->actingAs($this->admin)->get(route('admin.people.students.edit', $student))
            ->assertOk()
            ->assertSee('Valentina Barrios Julio')
            ->assertSee('Todavía no tiene acudientes');
    }

    public function test_student_document_must_be_unique(): void
    {
        Student::factory()->create(['document_number' => '1047123456']);

        $this->actingAs($this->admin)->post(route('admin.people.students.store'), [
            'document_type' => 'TI', 'document_number' => '1047.123.456', 'first_names' => 'A', 'last_names' => 'B',
        ])->assertSessionHasErrors(['document_number' => 'Ya hay una persona registrada con ese documento.']);
    }

    public function test_cannot_enroll_in_a_section_of_another_year(): void
    {
        $old = SchoolYear::create(['year' => 2026, 'starts_on' => '2026-01-26', 'ends_on' => '2026-11-27']);
        $oldSection = $old->sections()->create(['grade_id' => Grade::first()->id, 'name' => '1']);

        $this->actingAs($this->admin)->post(route('admin.people.students.store'), [
            'document_type' => 'RC', 'document_number' => '1100000001', 'first_names' => 'A', 'last_names' => 'B', 'section_id' => $oldSection->id,
        ])->assertSessionHasErrors('section_id');
    }

    public function test_links_a_new_guardian_who_becomes_primary(): void
    {
        $student = $this->enrolledStudent();

        $this->actingAs($this->admin)->post(route('admin.people.students.guardians.store', $student), [
            'guardian_document_type' => 'CC',
            'guardian_document_number' => '45.123.456',
            'guardian_first_names' => 'Yaneth',
            'guardian_last_names' => 'Julio Mercado',
            'guardian_phone' => '321 000 0000',
            'relationship' => 'mother',
        ])->assertSessionHasNoErrors();

        $guardian = $student->guardians()->first();
        $this->assertSame('45123456', $guardian->document_number);
        $this->assertSame(GuardianRelationship::Mother, $guardian->pivot->relationship);
        $this->assertTrue($guardian->pivot->is_primary);
    }

    public function test_sibling_reuses_the_existing_guardian_by_document_only(): void
    {
        $mother = Guardian::factory()->create(['document_number' => '45123456']);
        $older = $this->enrolledStudent('7.°');
        $older->guardians()->attach($mother->id, ['relationship' => 'mother', 'is_primary' => true]);
        $younger = $this->enrolledStudent('2.°');

        $this->actingAs($this->admin)->post(route('admin.people.students.guardians.store', $younger), [
            'guardian_document_number' => '45123456',
            'relationship' => 'mother',
        ])->assertSessionHasNoErrors()->assertSessionHas('status_message', fn ($m) => str_contains($m, 'ya estaba registrado'));

        $this->assertSame(1, Guardian::count());
        $this->assertCount(2, $mother->students);
    }

    public function test_cannot_link_the_same_guardian_twice(): void
    {
        $student = $this->enrolledStudent();
        $guardian = Guardian::factory()->create();
        $student->guardians()->attach($guardian->id, ['relationship' => 'mother', 'is_primary' => true]);

        $this->actingAs($this->admin)->post(route('admin.people.students.guardians.store', $student), [
            'guardian_document_number' => $guardian->document_number, 'relationship' => 'mother',
        ])->assertSessionHasErrors('guardian_document_number');
    }

    public function test_only_one_primary_guardian_and_unlinking_promotes_the_next(): void
    {
        $student = $this->enrolledStudent();
        [$mother, $father] = Guardian::factory()->count(2)->create();
        $student->guardians()->attach($mother->id, ['relationship' => 'mother', 'is_primary' => true]);
        $student->guardians()->attach($father->id, ['relationship' => 'father', 'is_primary' => false]);

        $this->actingAs($this->admin)->put(route('admin.people.students.guardians.update', [$student, $father]), [
            'relationship' => 'father', 'is_primary' => '1',
        ]);
        $this->assertSame([$father->id], $student->guardians()->wherePivot('is_primary', true)->pluck('guardians.id')->all());

        $this->actingAs($this->admin)->delete(route('admin.people.students.guardians.destroy', [$student, $father]));
        $this->assertSame([$mother->id], $student->guardians()->wherePivot('is_primary', true)->pluck('guardians.id')->all());
        $this->assertModelExists($father);
    }

    public function test_changes_section_and_withdraws_within_the_year(): void
    {
        $student = $this->enrolledStudent('3.°');
        $other = $this->section('3.°', '2');

        $this->actingAs($this->admin)->put(route('admin.people.students.enroll', $student), [
            'section_id' => $other->id, 'status' => 'withdrawn',
        ])->assertSessionHasNoErrors();

        $enrollment = $student->enrollmentFor($this->year);
        $this->assertSame(1, $student->enrollments()->count());
        $this->assertSame($other->id, $enrollment->section_id);
        $this->assertSame(EnrollmentStatus::Withdrawn, $enrollment->status);
        $this->assertTrue($enrollment->withdrawn_on->isToday());
    }

    public function test_a_section_with_students_cannot_be_deleted(): void
    {
        $student = $this->enrolledStudent('3.°');
        $section = $student->enrollmentFor($this->year)->section;

        $this->actingAs($this->admin)->delete(route('admin.academic.sections.destroy', $section))
            ->assertSessionHasErrors('section');

        $this->assertModelExists($section);
    }

    public function test_editing_a_person_updates_their_login(): void
    {
        $guardian = Guardian::factory()->create();
        $student = $this->enrolledStudent();
        $student->guardians()->attach($guardian->id, ['relationship' => 'mother', 'is_primary' => true]);
        $this->actingAs($this->admin)->post(route('admin.people.accounts.guardian', $guardian));

        $this->actingAs($this->admin)->put(route('admin.people.guardians.update', $guardian), [
            'document_type' => 'CC', 'document_number' => '99.888.777', 'first_names' => 'Rosa', 'last_names' => 'Meza', 'phone' => '', 'email' => '',
        ])->assertSessionHasNoErrors();

        $user = $guardian->fresh()->user;
        $this->assertSame('99888777', $user->document_number);
        $this->assertSame('Rosa Meza', $user->name);
    }

    public function test_guardian_account_gets_a_temporary_password_shown_once(): void
    {
        $guardian = Guardian::factory()->create(['email' => 'rosa@example.com']);

        $response = $this->actingAs($this->admin)->post(route('admin.people.accounts.guardian', $guardian));

        $slip = session('credentials')[0];
        $response->assertSessionHas('status_message');
        $user = $guardian->fresh()->user;
        $this->assertSame(Role::Guardian, $user->role);
        $this->assertTrue($user->must_change_password);
        $this->assertSame('rosa@example.com', $user->email);
        $this->assertSame($guardian->document_number, $slip['login']);
        $this->assertMatchesRegularExpression('/^[a-z]{4}-\d{4}$/', $slip['password']);
        $this->assertTrue(Hash::check($slip['password'], $user->password));

        // La ficha se ve en la página siguiente y no se vuelve a mostrar
        $this->actingAs($this->admin)->get(route('admin.people.guardians.edit', $guardian))->assertSee($slip['password']);
        $this->actingAs($this->admin)->get(route('admin.people.guardians.edit', $guardian))->assertDontSee($slip['password']);
    }

    public function test_teacher_who_is_also_a_guardian_keeps_a_single_account(): void
    {
        $teacher = User::factory()->role(Role::Teacher)->create(['document_number' => '45123456']);
        $guardian = Guardian::factory()->create(['document_number' => '45123456']);

        $this->actingAs($this->admin)->post(route('admin.people.accounts.guardian', $guardian))
            ->assertSessionMissing('credentials');

        $this->assertTrue($guardian->fresh()->user->is($teacher));
        $this->assertSame(Role::Teacher, $teacher->fresh()->role);
        $this->assertSame(2, User::count());
    }

    public function test_students_get_an_account_only_from_sixth_grade(): void
    {
        $young = $this->enrolledStudent('5.°');
        $older = $this->enrolledStudent('6.°');

        $this->actingAs($this->admin)->post(route('admin.people.accounts.student', $young))->assertSessionHasErrors('account');
        $this->assertNull($young->fresh()->user_id);

        $this->actingAs($this->admin)->post(route('admin.people.accounts.student', $older))->assertSessionHas('credentials');
        $this->assertSame(Role::Student, $older->fresh()->user->role);
    }

    public function test_bulk_creates_pending_guardian_accounts_with_a_printable_sheet(): void
    {
        $student = $this->enrolledStudent();
        $guardians = Guardian::factory()->count(3)->create();
        foreach ($guardians as $i => $guardian) {
            $student->guardians()->attach($guardian->id, ['relationship' => 'other', 'is_primary' => $i === 0]);
        }
        Guardian::factory()->create(); // sin acudidos: no se le crea cuenta

        $this->actingAs($this->admin)->post(route('admin.people.accounts.bulk'), ['type' => 'guardians'])
            ->assertOk()
            ->assertSee('Se crearon 3 cuentas.')
            ->assertSee('Contraseña temporal');

        $this->assertSame(3, Guardian::whereNotNull('user_id')->count());

        $this->actingAs($this->admin)->post(route('admin.people.accounts.bulk'), ['type' => 'guardians'])
            ->assertRedirect()
            ->assertSessionHas('status_message', 'No hay cuentas pendientes por crear.');
    }

    public function test_bulk_student_accounts_only_for_active_students_from_sixth(): void
    {
        $this->enrolledStudent('5.°');
        $eligible = $this->enrolledStudent('7.°');
        $withdrawn = $this->enrolledStudent('8.°');
        $withdrawn->enrollmentFor($this->year)->update(['status' => EnrollmentStatus::Withdrawn]);

        $this->actingAs($this->admin)->post(route('admin.people.accounts.bulk'), ['type' => 'students'])->assertOk();

        $this->assertSame([$eligible->id], Student::whereNotNull('user_id')->pluck('id')->all());
    }

    public function test_reset_and_deactivate_an_account_but_not_your_own(): void
    {
        $teacher = User::factory()->role(Role::Teacher)->create();

        $this->actingAs($this->admin)->post(route('admin.people.accounts.reset', $teacher))->assertSessionHas('credentials');
        $this->assertTrue($teacher->fresh()->must_change_password);

        $this->actingAs($this->admin)->post(route('admin.people.accounts.toggle', $teacher));
        $this->assertFalse($teacher->fresh()->is_active);

        $this->actingAs($this->admin)->post(route('admin.people.accounts.toggle', $this->admin))->assertForbidden();
        $this->actingAs($this->admin)->post(route('admin.people.accounts.reset', $this->admin))->assertForbidden();
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_admin_creates_teacher_accounts_and_cannot_demote_themselves(): void
    {
        $this->actingAs($this->admin)->post(route('admin.people.staff.store'), [
            'name' => 'Luis Martínez', 'document_number' => '72.345.678', 'email' => '', 'role' => 'teacher',
        ])->assertSessionHas('credentials');

        $teacher = User::firstWhere('document_number', '72345678');
        $this->assertSame(Role::Teacher, $teacher->role);
        $this->assertNull($teacher->email);
        $this->assertTrue($teacher->must_change_password);

        $this->actingAs($this->admin)->post(route('admin.people.staff.store'), [
            'name' => 'Otro', 'document_number' => '72345678', 'role' => 'teacher',
        ])->assertSessionHasErrors(['document_number' => 'Ya hay una cuenta con ese documento.']);

        $this->actingAs($this->admin)->post(route('admin.people.staff.store'), [
            'name' => 'Otro', 'document_number' => '1', 'role' => 'guardian',
        ])->assertSessionHasErrors(['role', 'document_number']);

        $this->actingAs($this->admin)->put(route('admin.people.staff.update', $this->admin), [
            'name' => $this->admin->name, 'document_number' => '11111111', 'email' => $this->admin->email, 'role' => 'teacher',
        ])->assertSessionHasErrors('role');
        $this->assertTrue($this->admin->fresh()->isAdmin());
    }

    public function test_staff_edit_does_not_open_family_accounts(): void
    {
        $guardianUser = User::factory()->role(Role::Guardian)->create();

        $this->actingAs($this->admin)->get(route('admin.people.staff.edit', $guardianUser))->assertNotFound();
    }

    public function test_deleting_a_student_removes_their_account_but_not_guardians(): void
    {
        $student = $this->enrolledStudent('7.°');
        $guardian = Guardian::factory()->create();
        $student->guardians()->attach($guardian->id, ['relationship' => 'mother', 'is_primary' => true]);
        $this->actingAs($this->admin)->post(route('admin.people.accounts.student', $student));
        $userId = $student->fresh()->user_id;

        $this->actingAs($this->admin)->delete(route('admin.people.students.destroy', $student))
            ->assertRedirect(route('admin.people.students.index'));

        $this->assertModelMissing($student);
        $this->assertNull(User::find($userId));
        $this->assertModelExists($guardian);
        $this->assertSame(0, Enrollment::count());
    }

    public function test_lists_filter_by_section_and_search(): void
    {
        $a = $this->enrolledStudent('3.°', ['first_names' => 'Salomé', 'last_names' => 'Cantillo Arrieta']);
        $b = $this->enrolledStudent('7.°', ['first_names' => 'Thiago', 'last_names' => 'Padilla Meza']);
        Student::factory()->create(['first_names' => 'Emmanuel', 'last_names' => 'Sin Sección']);

        $this->actingAs($this->admin)->get(route('admin.people.students.index', ['seccion' => $this->section('3.°')->id]))
            ->assertOk()->assertSee('Cantillo Arrieta, Salomé')->assertDontSee('Padilla Meza');

        $this->actingAs($this->admin)->get(route('admin.people.students.index', ['q' => 'thiago padilla']))
            ->assertSee('Padilla Meza, Thiago')->assertDontSee('Cantillo');

        $this->actingAs($this->admin)->get(route('admin.people.students.index', ['q' => $b->document_number]))
            ->assertSee('Padilla Meza, Thiago');

        $this->actingAs($this->admin)->get(route('admin.people.students.index', ['seccion' => 'sin-matricula']))
            ->assertSee('Sin Sección, Emmanuel')->assertDontSee('Salomé');

        $this->actingAs($this->admin)->get(route('admin.people.guardians.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.people.staff.index'))->assertOk()->assertSee($this->admin->name);
    }
}
