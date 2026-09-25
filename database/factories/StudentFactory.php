<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_type' => DocumentType::IdentityCard,
            'document_number' => (string) fake()->unique()->numberBetween(1040000000, 1049999999),
            'first_names' => fake()->firstName(),
            'last_names' => fake()->lastName().' '.fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-15 years', '-4 years')->format('Y-m-d'),
        ];
    }
}
