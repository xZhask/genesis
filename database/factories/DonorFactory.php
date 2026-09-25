<?php

namespace Database\Factories;

use App\Models\Donor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donor>
 */
class DonorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'description' => 'Empresa aliada',
            'website' => null,
            'consent_at' => now(),
            'is_visible' => true,
            'position' => 0,
        ];
    }
}
