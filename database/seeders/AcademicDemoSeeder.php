<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Año lectivo de ejemplo para desarrollo local: 2026 como año actual, con
 * los periodos 1 a 3 cerrados (para ver la regla de solo lectura) y una
 * sección por grado, más una segunda en 3.° y 6.°. El portal real empieza
 * en 2027 y ese año lo crea el admin desde Académico.
 */
class AcademicDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@genesis.test')->first();

        $year = SchoolYear::create(['year' => 2026, 'starts_on' => '2026-01-26', 'ends_on' => '2026-11-27']);
        $year->createPeriods((int) config('school.academic.periods'));
        $year->makeCurrent();

        foreach ($year->periods as $period) {
            if ($period->ends_on->isPast() && $admin) {
                $period->close($admin);
            }
        }

        foreach (Grade::ordered() as $grade) {
            $year->sections()->create(['grade_id' => $grade->id, 'name' => '1']);

            if (in_array($grade->name, ['3.°', '6.°'], true)) {
                $year->sections()->create(['grade_id' => $grade->id, 'name' => '2']);
            }
        }
    }
}
