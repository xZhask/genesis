<?php

namespace Database\Factories;

use App\Models\DonationAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonationAccount>
 */
class DonationAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => 'Banco de ejemplo · Cuenta de ahorros',
            'number' => fake()->numerify('###-######-##'),
            'holder' => 'Centro Educativo Cristiano Génesis',
            'holder_document' => 'NIT 900.000.000-0',
            'is_visible' => true,
            'position' => 0,
        ];
    }
}
