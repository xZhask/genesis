<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'excerpt' => fake()->sentence(16),
            'body' => implode("\n\n", fake()->paragraphs(3)),
            'status' => PostStatus::Published,
            'published_at' => fake()->dateTimeBetween('-2 months', '-1 day'),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => PostStatus::Draft, 'published_at' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['status' => PostStatus::Published, 'published_at' => now()->addDays(3)]);
    }
}
