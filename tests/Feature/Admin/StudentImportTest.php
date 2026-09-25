<?php

namespace Tests\Feature\Admin;

use App\Enums\DocumentType;
use App\Enums\GuardianRelationship;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private SchoolYear $year;

    private const HEADER = 'tipo_documento;documento;nombres;apellidos;fecha_nacimiento;grado;seccion;acudiente_tipo_documento;acudiente_documento;acudiente_nombres;acudiente_apellidos;parentesco;acudiente_telefono;acudiente_correo;acudiente_principal';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
        $this->admin = User::factory()->admin()->create();
        $this->year = SchoolYear::create(['year' => 2027, 'starts_on' => '2027-01-25', 'ends_on' => '2027-11-26']);
        $this->year->makeCurrent();
        foreach (['Transición', '3.°', '7.°'] as $grade) {
            $this->year->sections()->create(['grade_id' => Grade::firstWhere('name', $grade)->id, 'name' => '1']);
        }
    }

    private function csv(array $rows, string $encoding = 'UTF-8', string $header = self::HEADER): UploadedFile
    {
        $content = implode("\r\n", [$header, ...$rows])."\r\n;;;;\r\n";
        if ($encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, $encoding, 'UTF-8');
        }
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, $content);

        return new UploadedFile($path, 'estudiantes.csv', 'text/csv', null, true);
    }

    public function test_template_download_has_the_expected_columns(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.people.import.template'));

        $response->assertOk()->assertDownload('plantilla-estudiantes.csv');
        $this->assertStringStartsWith("\u{FEFF}tipo_documento;documento;nombres", $response->getContent());
    }

    public function test_preview_shows_a_summary_and_saves_nothing(): void
    {
        $file = $this->csv([
            'TI;1.047.100.001;VALENTINA;BARRIOS JULIO;14/03/2018;3;1;CC;45123456;Yaneth;Julio de la Hoz;Mamá;321 000 0000;yaneth@example.com;si',
            ';1047100002;Samuel;Barrios Julio;02/05/2014;séptimo;;;45123456;;;madre;;;',
            'RC;1100200300;Sara;Meza;;Transición;;;72000111;Álvaro;Meza Castro;papá;;;',
        ]);

        $this->actingAs($this->admin)->post(route('admin.people.import.preview'), ['file' => $file])
            ->assertOk()
            ->assertSee('Todo está en orden')
            ->assertSee('3 nuevos')
            ->assertSee('2 nuevos')
            ->assertSee('7.° 1');

        $this->assertSame(0, Student::count());
        $this->assertNotNull(session('import'));
    }

    public function test_confirming_imports_students_guardians_siblings_and_enrollments(): void
    {
        $file = $this->csv([
            'TI;1.047.100.001;VALENTINA;BARRIOS JULIO;14/03/2018;3;1;CC;45123456;Yaneth;Julio de la Hoz;Mamá;321 000 0000;yaneth@example.com;si',
            'TI;1047100001;VALENTINA;BARRIOS JULIO;14/03/2018;3;1;;72999888;Rafael;Barrios Ortega;padre;;;',
            ';1047100002;Samuel;Barrios Julio;02/05/2014;séptimo;;;45123456;;;madre;;;',
            ';1100200300;Sara;Meza;;Transición;;;;;;;;;',
        ]);
        $this->actingAs($this->admin)->post(route('admin.people.import.preview'), ['file' => $file])->assertOk();
        $path = session('import')['path'];

        $this->actingAs($this->admin)->post(route('admin.people.import.store'))
            ->assertRedirect(route('admin.people.students.index', ['lectivo' => 2027]))
            ->assertSessionHas('status_message', fn ($m) => str_contains($m, '3 estudiantes nuevos, 2 acudientes nuevos y 3 vínculos nuevos'));

        $valentina = Student::firstWhere('document_number', '1047100001');
        $this->assertSame('Valentina', $valentina->first_names);
        $this->assertSame('Barrios Julio', $valentina->last_names);
        $this->assertSame('2018-03-14', $valentina->birth_date->toDateString());
        $this->assertSame('3.° 1', $valentina->enrollmentFor($this->year)->section->label());
        $this->assertCount(2, $valentina->guardians);
        $this->assertSame('Yaneth', $valentina->primaryGuardian()->first_names);

        $mother = Guardian::firstWhere('document_number', '45123456');
        $this->assertSame('Julio de la Hoz', $mother->last_names);
        $this->assertSame(GuardianRelationship::Mother, $mother->students->first()->pivot->relationship);
        $this->assertCount(2, $mother->students, 'Los hermanos comparten acudiente');

        // Sin tipo de documento: TI desde 1.°, RC en preescolar
        $this->assertSame(DocumentType::IdentityCard, Student::firstWhere('document_number', '1047100002')->document_type);
        $this->assertSame(DocumentType::CivilRegistry, Student::firstWhere('document_number', '1100200300')->document_type);

        Storage::disk('local')->assertMissing($path);
        $this->assertNull(session('import'));
    }

    public function test_reimporting_updates_instead_of_duplicating(): void
    {
        $student = Student::factory()->create(['document_number' => '1047100001', 'document_type' => DocumentType::Passport, 'first_names' => 'Vale']);

        $file = $this->csv([';1047100001;Valentina;Barrios Julio;;7;1;;;;;;;;']);
        $this->actingAs($this->admin)->post(route('admin.people.import.preview'), ['file' => $file])->assertSee('1 ya registrado');
        $this->actingAs($this->admin)->post(route('admin.people.import.store'));

        $this->assertSame(1, Student::count());
        $student->refresh();
        $this->assertSame('Valentina', $student->first_names);
        $this->assertSame(DocumentType::Passport, $student->document_type, 'Sin tipo en el archivo se conserva el registrado');
        $this->assertSame('7.° 1', $student->enrollmentFor($this->year)->section->label());
    }

    public function test_excel_windows_1252_with_alias_headers_is_read(): void
    {
        $file = $this->csv(['1047100009;José Ángel;Pájaro Núñez;3.°;Candelaria;Núñez Salgado;30111222;Abuela'], 'Windows-1252',
            'Documento;Nombres;Apellidos;Grado;Acudiente nombres;Acudiente apellidos;Acudiente documento;Parentesco');

        $this->actingAs($this->admin)->post(route('admin.people.import.preview'), ['file' => $file])->assertSee('Todo está en orden');
        $this->actingAs($this->admin)->post(route('admin.people.import.store'));

        $student = Student::firstWhere('document_number', '1047100009');
        $this->assertSame('José Ángel', $student->first_names);
        $this->assertSame('Pájaro Núñez', $student->last_names);
        $this->assertSame(GuardianRelationship::Grandparent, $student->guardians->first()->pivot->relationship);
    }

    public function test_errors_are_listed_by_row_and_nothing_is_imported(): void
    {
        $file = $this->csv([
            'TI;1047100001;Valentina;Barrios;14/03/2018;3;1;;;;;;;;',
            'XX;1047100002;Samuel;;31/02/2014;10;1;;;;;;;;',
            'TI;1047100003;Sara;Meza;;3;2;;45123456;;Castro;madre;;correo-malo;',
            'TI;1047100001;Valentina;Barrios;14/03/2018;7;1;;;;;;;;',
        ]);

        $this->actingAs($this->admin)->post(route('admin.people.import.preview'), ['file' => $file])
            ->assertOk()
            ->assertSee('Hay 3 problemas en el archivo')
            ->assertSee('Fila 3: falta «apellidos»; tipo de documento «XX» no reconocido', false)
            ->assertSee('fecha de nacimiento «31/02/2014» no válida', false)
            ->assertSee('grado «10» no reconocido', false)
            ->assertSee('Fila 4:', false)
            ->assertSee('la sección 3.° 2 no existe en 2027', false)
            ->assertSee('falta «acudiente_nombres»', false)
            ->assertSee('correo del acudiente «correo-malo» no válido', false)
            ->assertSee('Fila 5: el estudiante 1047100001 aparece en otra fila con una sección distinta.', false);

        $this->assertNull(session('import'));
        $this->assertSame([], Storage::disk('local')->allFiles('imports'));

        $this->actingAs($this->admin)->post(route('admin.people.import.store'))
            ->assertRedirect(route('admin.people.import.create'))
            ->assertSessionHasErrors('file');
        $this->assertSame(0, Student::count());
    }

    public function test_missing_required_columns_and_wrong_file_type(): void
    {
        $file = $this->csv(['Valentina;Barrios'], 'UTF-8', 'nombres;apellidos');
        $this->actingAs($this->admin)->post(route('admin.people.import.preview'), ['file' => $file])
            ->assertSee('Faltan columnas obligatorias: documento, grado.');

        $xlsx = UploadedFile::fake()->create('estudiantes.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->actingAs($this->admin)->post(route('admin.people.import.preview'), ['file' => $xlsx])
            ->assertSessionHasErrors('file');
    }

    public function test_discarding_deletes_the_pending_file(): void
    {
        $this->actingAs($this->admin)->post(route('admin.people.import.preview'), ['file' => $this->csv([';1047100001;Ana;Ruiz;;3;;;;;;;;;'])]);
        $path = session('import')['path'];

        $this->actingAs($this->admin)->delete(route('admin.people.import.discard'))->assertRedirect(route('admin.people.import.create'));

        Storage::disk('local')->assertMissing($path);
        $this->assertSame(0, Student::count());
    }
}
