<?php

namespace Database\Seeders;

use App\Enums\VolunteerArea;
use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerStatus;
use App\Models\DonationAccount;
use App\Models\Donor;
use App\Models\Testimonial;
use App\Models\VolunteerApplication;
use Illuminate\Database\Seeder;

/**
 * Apóyanos con datos FICTICIOS (solo desarrollo local), todos marcados
 * "(ejemplo)". Las cuentas, los donantes y los testimonios reales los carga
 * el colegio desde el admin; ver docs/pendientes.md.
 */
class SupportSeeder extends Seeder
{
    public function run(): void
    {
        DonationAccount::create([
            'label' => 'Banco de ejemplo · Cuenta de ahorros',
            'number' => '000-000000-00',
            'holder' => 'Centro Educativo Cristiano Génesis',
            'holder_document' => 'NIT 900.000.000-0 (ejemplo)',
            'position' => 1,
        ]);
        DonationAccount::create(['label' => 'Nequi (ejemplo)', 'number' => '300 000 0000', 'position' => 2]);

        $donors = [
            ['Ferretería El Progreso (ejemplo)', 'Donó la pintura para tres aulas', true],
            ['Iglesia aliada (ejemplo)', 'Apoya el refrigerio de preescolar', true],
            ['Familia Pérez Gómez (ejemplo)', 'Familia donante', true],
            ['Fundación amiga (ejemplo)', 'Dotación de la biblioteca', true],
            ['Donante oculto (ejemplo)', 'No autorizó aparecer todavía', false],
        ];
        foreach ($donors as $i => [$name, $description, $visible]) {
            $donor = new Donor(['name' => $name, 'description' => $description, 'is_visible' => $visible, 'position' => $i + 1]);
            $donor->consent_at = $visible ? now() : null;
            $donor->save();
        }

        $testimonials = [
            ['Pintamos tres aulas en un fin de semana. Ver la cara de los niños el lunes valió cada brochazo.', 'María Fernanda (ejemplo)', 'acudiente y voluntaria'],
            ['Di un taller de carpintería a los de 8.°. Salí convencido de que enseñar también es servir.', 'Jorge (ejemplo)', 'voluntario'],
        ];
        foreach ($testimonials as $i => [$quote, $author, $role]) {
            $testimonial = new Testimonial(['quote' => $quote, 'author' => $author, 'role' => $role, 'position' => $i + 1]);
            $testimonial->consent_at = now();
            $testimonial->save();
        }

        $applications = [
            ['Luisa Martínez (ejemplo)', [VolunteerArea::Reading], VolunteerAvailability::WeekdayAfternoon, VolunteerStatus::New, 1],
            ['Carlos Rodríguez (ejemplo)', [VolunteerArea::Spaces, VolunteerArea::Events], VolunteerAvailability::Weekends, VolunteerStatus::New, 3],
            ['Andrea Gómez (ejemplo)', [VolunteerArea::Skills], VolunteerAvailability::Occasional, VolunteerStatus::Contacted, 12],
        ];
        foreach ($applications as $i => [$name, $areas, $availability, $status, $daysAgo]) {
            $application = VolunteerApplication::factory()->create([
                'name' => $name,
                'phone' => '+57300000000'.($i + 1),
                'areas' => $areas,
                'availability' => $availability,
                'created_at' => now()->subDays($daysAgo),
            ]);
            $application->status = $status;
            $application->admin_note = $status === VolunteerStatus::Contacted ? 'Llamada de ejemplo: ayudará en la feria de ciencias.' : null;
            $application->save();
        }
    }
}
