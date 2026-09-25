<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Catálogo académico inicial: áreas, materias (lista del colegio del
 * 25/09/2026) y plan de estudios PROVISIONAL por grado.
 *
 * Se puede ejecutar en producción (no crea usuarios ni datos personales):
 *   php artisan db:seed --class=AcademicCatalogSeeder
 * Solo agrega lo que falta: no sobrescribe lo que el admin ya editó.
 */
class AcademicCatalogSeeder extends Seeder
{
    /** Áreas obligatorias (Ley 115, art. 23) y las materias del colegio en cada una. */
    private const AREAS = [
        'Humanidades' => ['Español', 'Inglés'],
        'Matemáticas' => ['Matemáticas', 'Álgebra', 'Geometría', 'Estadística'],
        'Ciencias Naturales y Educación Ambiental' => ['Biología', 'Física', 'Química'],
        'Ciencias Sociales' => ['Sociales e historia', 'Constitución Política y Democracia', 'Filosofía'],
        'Tecnología e Informática' => ['Informática'],
        'Educación Artística y Cultural' => ['Música', 'Artística'],
        'Educación Física, Recreación y Deportes' => ['Educación Física'],
        'Educación Religiosa' => ['Educación cristiana'],
        'Educación Ética y en Valores Humanos' => ['Cátedra de Educación Emocional'],
        'Educación Económica y Financiera' => ['Educación financiera'],
        // Preescolar: evaluación cualitativa por dimensiones (Decreto 2247 de 1997)
        'Dimensiones del desarrollo' => [
            'Dimensión cognitiva', 'Dimensión comunicativa', 'Dimensión corporal', 'Dimensión estética',
            'Dimensión ética', 'Dimensión socioafectiva', 'Dimensión espiritual',
        ],
    ];

    /** Intensidad horaria semanal provisional (primaria, tomada del boletín de 3.°). */
    private const PRIMARY = [
        'Español' => 5, 'Inglés' => 4, 'Matemáticas' => 5, 'Geometría' => 1, 'Estadística' => 1,
        'Biología' => 4, 'Sociales e historia' => 5, 'Informática' => 1, 'Música' => 1, 'Artística' => 1,
        'Educación Física' => 1, 'Educación cristiana' => 1, 'Cátedra de Educación Emocional' => 1,
        'Educación financiera' => 1,
    ];

    private const SECONDARY = [
        'Español' => 4, 'Inglés' => 4, 'Geometría' => 1, 'Estadística' => 1, 'Biología' => 2, 'Física' => 1,
        'Química' => 1, 'Sociales e historia' => 3, 'Constitución Política y Democracia' => 1, 'Informática' => 2,
        'Música' => 1, 'Artística' => 1, 'Educación Física' => 2, 'Educación cristiana' => 1,
        'Cátedra de Educación Emocional' => 1, 'Educación financiera' => 1,
    ];

    public function run(): void
    {
        $position = 0;
        foreach (self::AREAS as $areaName => $subjects) {
            $area = Area::firstOrCreate(['name' => $areaName], ['position' => ++$position]);

            foreach ($subjects as $i => $subjectName) {
                Subject::firstOrCreate(['name' => $subjectName], ['area_id' => $area->id, 'position' => $i + 1]);
            }
        }

        $subjects = Subject::pluck('id', 'name');

        foreach (Grade::ordered() as $grade) {
            // No se toca un plan que el admin ya armó
            if ($grade->subjects()->exists()) {
                continue;
            }

            $grade->subjects()->attach(
                collect($this->planFor($grade))->mapWithKeys(fn ($hours, $name) => [$subjects[$name] => ['weekly_hours' => $hours]])
            );
        }
    }

    /** @return array<string, int> materia => horas */
    private function planFor(Grade $grade): array
    {
        if ($grade->isPreschool()) {
            return collect(self::AREAS['Dimensiones del desarrollo'])->mapWithKeys(fn ($d) => [$d => 0])->all();
        }

        if ($grade->level === 'primary') {
            return self::PRIMARY;
        }

        // Secundaria: Matemáticas en 6.° y 7.°, Álgebra en 8.° y 9.°; Filosofía en 9.°
        $plan = self::SECONDARY + (in_array($grade->name, ['8.°', '9.°'], true) ? ['Álgebra' => 4] : ['Matemáticas' => 4]);

        if ($grade->name === '9.°') {
            $plan['Filosofía'] = 1;
        }

        return $plan;
    }
}
