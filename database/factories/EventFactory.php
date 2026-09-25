<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+2 months');
        $start->setTime(fake()->numberBetween(7, 15), 0);

        return [
            'title' => fake()->sentence(4),
            'description' => null,
            'starts_at' => $start,
            'ends_at' => null,
            'all_day' => false,
            'location' => null,
            'level' => null,
        ];
    }
}
