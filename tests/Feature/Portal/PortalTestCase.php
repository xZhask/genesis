<?php

namespace Tests\Feature\Portal;

use App\Enums\Role;
use App\Models\Area;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Escenario común: año 2026 con 4 periodos, hoy dentro del periodo 4,
 * 7.° 1 con dos estudiantes y un docente de Matemáticas, y 3.° 1 con otro
 * docente.
 */
abstract class PortalTestCase extends TestCase
{
    use RefreshDatabase;

    protected SchoolYear $year;

    protected Section $seventh;

    protected Section $third;

    protected Subject $math;

    protected User $teacher;

    protected User $otherTeacher;

    protected TeacherAssignment $assignment;

    protected TeacherAssignment $otherAssignment;

    protected Student $andres;

    protected Student $sara;

    protected Student $lucia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->year = SchoolYear::create(['year' => 2026, 'starts_on' => '2026-01-26', 'ends_on' => '2026-11-27']);
        $this->year->createPeriods(4);
        $this->year->makeCurrent();
        $this->year->load('periods');
        Carbon::setTestNow($this->year->periods[3]->starts_on->copy()->addDays(5)->setTime(10, 0));

        $this->math = Subject::create(['name' => 'Matemáticas', 'area_id' => Area::create(['name' => 'Matemáticas'])->id]);
        foreach (['7.°', '3.°'] as $name) {
            Grade::firstWhere('name', $name)->subjects()->attach($this->math->id, ['weekly_hours' => 4]);
        }

        $this->seventh = $this->year->sections()->create(['grade_id' => Grade::firstWhere('name', '7.°')->id, 'name' => '1']);
        $this->third = $this->year->sections()->create(['grade_id' => Grade::firstWhere('name', '3.°')->id, 'name' => '1']);

        $this->teacher = User::factory()->role(Role::Teacher)->create(['name' => 'Edgar Ojoalegre']);
        $this->otherTeacher = User::factory()->role(Role::Teacher)->create();
        $this->assignment = TeacherAssignment::create(['teacher_id' => $this->teacher->id, 'section_id' => $this->seventh->id, 'subject_id' => $this->math->id]);
        $this->otherAssignment = TeacherAssignment::create(['teacher_id' => $this->otherTeacher->id, 'section_id' => $this->third->id, 'subject_id' => $this->math->id]);

        $this->andres = Student::factory()->create(['first_names' => 'Andrés', 'last_names' => 'Pérez Díaz']);
        $this->sara = Student::factory()->create(['first_names' => 'Sara', 'last_names' => 'Arrieta Meza']);
        $this->lucia = Student::factory()->create(['first_names' => 'Lucía', 'last_names' => 'Pérez Díaz']);
        Enrollment::place($this->andres, $this->seventh);
        Enrollment::place($this->sara, $this->seventh);
        Enrollment::place($this->lucia, $this->third);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function enrollmentId(Student $student): int
    {
        return $student->enrollmentFor($this->year)->id;
    }

    /** Acudiente con cuenta, vinculado a los estudiantes dados. */
    protected function guardianOf(Student ...$students): User
    {
        $user = User::factory()->role(Role::Guardian)->create();
        $guardian = Guardian::factory()->create(['user_id' => $user->id]);
        foreach ($students as $i => $student) {
            $student->guardians()->attach($guardian->id, ['relationship' => 'mother', 'is_primary' => $i === 0]);
        }

        return $user;
    }
}
