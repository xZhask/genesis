<?php

namespace Database\Seeders;

use App\Enums\AdmissionStatus;
use App\Enums\Role;
use App\Models\AdmissionRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos de prueba para desarrollo local. Nunca ejecutar en producción:
 * las contraseñas son conocidas.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Administración Génesis',
            'email' => 'admin@genesis.test',
        ]);

        // Un usuario por rol para probar permisos (contraseña: password)
        User::factory()->role(Role::Teacher)->create(['name' => 'Luis Martínez', 'email' => 'docente@genesis.test']);
        User::factory()->role(Role::Guardian)->create(['name' => 'Marta Díaz', 'email' => 'acudiente@genesis.test']);
        User::factory()->role(Role::Student)->create(['name' => 'Andrés Pérez', 'email' => 'estudiante@genesis.test']);

        // Solicitudes en distintos estados, con su historial
        AdmissionRequest::factory()->count(6)->create();

        AdmissionRequest::factory()->count(3)->create()->each(
            fn ($a) => $a->changeStatus(AdmissionStatus::InReview, $admin, note: 'Se llamó a la familia.')
        );

        AdmissionRequest::factory()->count(3)->create()->each(function ($a, $i) use ($admin) {
            $a->changeStatus(AdmissionStatus::InReview, $admin);
            $a->changeStatus(AdmissionStatus::InterviewScheduled, $admin, now()->addDays($i + 1)->setTime(8 + $i, 0));
        });

        AdmissionRequest::factory()->count(2)->create()->each(function ($a) use ($admin) {
            $a->changeStatus(AdmissionStatus::InterviewScheduled, $admin, now()->subDays(5)->setTime(9, 0));
            $a->changeStatus(AdmissionStatus::Accepted, $admin, note: 'Documentos completos.');
        });

        AdmissionRequest::factory()->create()->changeStatus(AdmissionStatus::Withdrawn, $admin, note: 'La familia se trasladó de municipio.');

        $this->call([NewsAndEventsSeeder::class, GallerySeeder::class, ResourcesSeeder::class]);
    }
}
