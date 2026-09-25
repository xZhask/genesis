<?php

namespace Database\Seeders;

use App\Enums\ContactField;
use App\Enums\DocumentType;
use App\Enums\GuardianRelationship;
use App\Models\ContactUpdateRequest;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Estudiantes y acudientes FICTICIOS para desarrollo local (nunca en
 * producción). Llena las secciones del año de ejemplo y vincula las cuentas
 * de prueba: acudiente@genesis.test es madre de dos estudiantes y
 * estudiante@genesis.test es uno de ellos, en 7.°.
 */
class PeopleDemoSeeder extends Seeder
{
    private const GIRLS = ['Valentina', 'Sofía', 'Isabella', 'Mariana', 'Luciana', 'Salomé', 'Gabriela', 'Daniela', 'Sara', 'Antonella', 'María José', 'Ana Lucía'];

    private const BOYS = ['Santiago', 'Samuel', 'Matías', 'Sebastián', 'Emmanuel', 'Juan José', 'Thiago', 'Daniel', 'Jerónimo', 'Miguel Ángel', 'Nicolás', 'David'];

    private const SURNAMES = ['Barrios', 'Julio', 'Mercado', 'Pájaro', 'Arrieta', 'Cantillo', 'Villalba', 'Ortega', 'Padilla', 'Salgado', 'Meza', 'Castro', 'Herrera', 'Gómez', 'Ruiz', 'Torres'];

    private const ADULTS_F = ['Yaneth', 'Luz Marina', 'Candelaria', 'Rosa', 'Deisy', 'Katherine', 'Yuleidis', 'Mónica'];

    private const ADULTS_M = ['Álvaro', 'Rafael', 'Wilmer', 'Édgar', 'Jairo', 'Libardo', 'Orlando', 'Hernán'];

    private int $document = 1047100000;

    public function run(): void
    {
        $year = SchoolYear::current();
        if (! $year) {
            return;
        }

        mt_srand(2026);

        // Cuentas de prueba del DatabaseSeeder: ahora ingresan con documento
        User::where('email', 'docente@genesis.test')->update(['document_number' => '22334455']);
        $guardianUser = User::where('email', 'acudiente@genesis.test')->first();
        $studentUser = User::where('email', 'estudiante@genesis.test')->first();

        $sections = $year->sections()->with('grade')->get()->sortBy(fn ($s) => [$s->grade->position, $s->name]);

        foreach ($sections as $section) {
            $count = $section->grade->isPreschool() ? 4 : 6;
            for ($i = 0; $i < $count; $i++) {
                $girl = mt_rand(0, 1) === 1;
                $lastNames = $this->pick(self::SURNAMES).' '.$this->pick(self::SURNAMES);
                $student = Student::create([
                    'document_type' => $section->grade->isPreschool() ? DocumentType::CivilRegistry : DocumentType::IdentityCard,
                    'document_number' => (string) $this->document++,
                    'first_names' => $this->pick($girl ? self::GIRLS : self::BOYS),
                    'last_names' => $lastNames,
                    'birth_date' => now()->subYears(3 + $section->grade->position)->subDays(mt_rand(0, 360))->toDateString(),
                ]);
                Enrollment::place($student, $section);

                $mother = Guardian::create([
                    'document_type' => DocumentType::Citizenship,
                    'document_number' => (string) mt_rand(30000000, 45999999),
                    'first_names' => $this->pick(self::ADULTS_F),
                    'last_names' => explode(' ', $lastNames)[1].' '.$this->pick(self::SURNAMES),
                    'phone' => '3'.mt_rand(10, 23).' '.mt_rand(100, 999).' '.mt_rand(1000, 9999),
                ]);
                $student->guardians()->attach($mother->id, ['relationship' => GuardianRelationship::Mother, 'is_primary' => true]);

                if (mt_rand(1, 10) <= 4) {
                    $father = Guardian::create([
                        'document_type' => DocumentType::Citizenship,
                        'document_number' => (string) mt_rand(72000000, 73999999),
                        'first_names' => $this->pick(self::ADULTS_M),
                        'last_names' => explode(' ', $lastNames)[0].' '.$this->pick(self::SURNAMES),
                        'phone' => '3'.mt_rand(10, 23).' '.mt_rand(100, 999).' '.mt_rand(1000, 9999),
                    ]);
                    $student->guardians()->attach($father->id, ['relationship' => GuardianRelationship::Father, 'is_primary' => false]);
                }
            }
        }

        // Familia de las cuentas de prueba: Marta Díaz, madre de Andrés (7.°) y Lucía (3.°)
        $marta = Guardian::create([
            'document_type' => DocumentType::Citizenship,
            'document_number' => '45123456',
            'first_names' => 'Marta',
            'last_names' => 'Díaz Barrios',
            'phone' => '321 000 0000',
            'email' => 'acudiente@genesis.test',
        ]);
        if ($guardianUser) {
            $guardianUser->update(['document_number' => '45123456']);
            $marta->user()->associate($guardianUser)->save();
        }
        // Un cambio de teléfono pedido desde el portal, para ver la revisión en el admin
        ContactUpdateRequest::submit($marta, ContactField::Phone, '321 555 0142');

        $andres = Student::create([
            'document_type' => DocumentType::IdentityCard,
            'document_number' => '1047999001',
            'first_names' => 'Andrés',
            'last_names' => 'Pérez Díaz',
            'birth_date' => now()->subYears(12)->toDateString(),
        ]);
        Enrollment::place($andres, $sections->first(fn ($s) => $s->grade->name === '7.°'));
        if ($studentUser) {
            $studentUser->update(['document_number' => '1047999001']);
            $andres->user()->associate($studentUser)->save();
        }

        $lucia = Student::create([
            'document_type' => DocumentType::IdentityCard,
            'document_number' => '1047999002',
            'first_names' => 'Lucía',
            'last_names' => 'Pérez Díaz',
            'birth_date' => now()->subYears(8)->toDateString(),
        ]);
        Enrollment::place($lucia, $sections->first(fn ($s) => $s->grade->name === '3.°'));

        foreach ([$andres, $lucia] as $child) {
            $child->guardians()->attach($marta->id, ['relationship' => GuardianRelationship::Mother, 'is_primary' => true]);
        }
    }

    private function pick(array $options): string
    {
        return $options[mt_rand(0, count($options) - 1)];
    }
}
