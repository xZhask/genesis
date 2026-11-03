<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use App\Support\PeopleExport;
use App\Support\StudentImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class PeopleExportTest extends TestCase
{
    use RefreshDatabase;

    private SchoolYear $year;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->year = SchoolYear::create(['year' => 2027, 'starts_on' => '2027-01-25', 'ends_on' => '2027-11-26']);
        $this->year->makeCurrent();
        $section = $this->year->sections()->create(['grade_id' => Grade::firstWhere('name', '7.°')->id, 'name' => '1']);

        // El nombre con & comprueba que el XML queda bien escapado
        $this->student = Student::factory()->create([
            'document_number' => '0104799',
            'first_names' => 'Andrés & Co',
            'last_names' => 'Pérez',
            'birth_date' => '2013-03-04',
        ]);
        Enrollment::place($this->student, $section);
        $guardian = Guardian::factory()->create(['first_names' => 'Marta', 'last_names' => 'Díaz', 'phone' => '3001234567', 'email' => 'marta@example.com']);
        $this->student->guardians()->attach($guardian->id, ['relationship' => 'mother', 'is_primary' => true]);
    }

    /** @return array<string, string> archivo dentro del xlsx => contenido */
    private function unzip(string $content): array
    {
        $path = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($path, $content);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'El archivo no es un zip válido');

        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $files[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
        }
        $zip->close();
        unlink($path);

        return $files;
    }

    public function test_admin_downloads_a_valid_workbook_with_students_and_staff(): void
    {
        $teacher = User::factory()->role(Role::Teacher)->create(['name' => 'Edgar Ojoalegre', 'birth_date' => '1985-06-07']);

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.people.export'))->assertOk();

        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));

        $files = $this->unzip($response->getContent());

        foreach (['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/styles.xml', 'xl/worksheets/sheet1.xml', 'xl/worksheets/sheet2.xml'] as $name) {
            $this->assertArrayHasKey($name, $files);
            $this->assertNotFalse(simplexml_load_string($files[$name]), "XML inválido: {$name}");
        }

        $students = $files['xl/worksheets/sheet1.xml'];
        $this->assertStringContainsString('Andrés &amp; Co', $students);
        $this->assertStringContainsString('marta@example.com', $students);
        // El documento va como texto: conserva el cero inicial
        $this->assertStringContainsString('<t xml:space="preserve">0104799</t>', $students);
        $this->assertStringContainsString('04/03/2013', $students);

        $staff = $files['xl/worksheets/sheet2.xml'];
        $this->assertStringContainsString($teacher->name, $staff);
        $this->assertStringContainsString('07/06/1985', $staff);
    }

    public function test_it_never_includes_passwords(): void
    {
        $admin = User::factory()->admin()->create();

        $files = $this->unzip($this->actingAs($admin)->get(route('admin.people.export'))->getContent());

        $this->assertStringNotContainsString($admin->getAuthPassword(), implode('', $files));
        $this->assertStringNotContainsStringIgnoringCase('contrase', implode('', $files));
    }

    public function test_a_formula_typed_in_a_name_stays_as_text(): void
    {
        $this->student->update(['last_names' => '=HYPERLINK("http://x")']);

        $files = $this->unzip((new PeopleExport($this->year))->build());

        $this->assertStringNotContainsString('<f>', $files['xl/worksheets/sheet1.xml']);
        $this->assertStringContainsString('t="inlineStr"', $files['xl/worksheets/sheet1.xml']);
    }

    public function test_student_without_guardian_or_enrollment_also_appears(): void
    {
        Student::factory()->create(['first_names' => 'Sin', 'last_names' => 'Vínculos']);

        $row = collect((new PeopleExport($this->year))->studentRows())->first(fn ($r) => $r[2] === 'Sin');

        $this->assertNull($row[5]);
        $this->assertSame('Sin matrícula este año', end($row));
    }

    public function test_the_student_sheet_can_be_imported_back(): void
    {
        $export = new PeopleExport($this->year);
        $path = tempnam(sys_get_temp_dir(), 'csv');
        $handle = fopen($path, 'w');
        fputcsv($handle, $export->studentHeaders(), ';', '"', '');
        foreach ($export->studentRows() as $row) {
            fputcsv($handle, $row, ';', '"', '');
        }
        fclose($handle);

        $import = (new StudentImport($this->year))->parse($path);
        unlink($path);

        $this->assertTrue($import->isValid(), implode(' | ', $import->errors));
        $this->assertCount(1, $import->rows);
    }
}
