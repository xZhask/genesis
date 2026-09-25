<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guardian>
 */
class GuardianFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_type' => DocumentType::Citizenship,
            'document_number' => (string) fake()->unique()->numberBetween(22000000, 99999999),
            'first_names' => fake()->firstName(),
            'last_names' => fake()->lastName().' '.fake()->lastName(),
            'phone' => '3'.fake()->numerify('## ### ####'),
            'email' => null,
        ];
    }
}
