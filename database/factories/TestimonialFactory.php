<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quote' => fake()->sentence(18),
            'author' => fake()->name(),
            'role' => 'acudiente y voluntaria',
            'consent_at' => now(),
            'is_visible' => true,
            'position' => 0,
        ];
    }
}
