<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Enums\ResourceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Recurso sin archivo real (para tests de permisos y listados).
 *
 * @extends Factory<\App\Models\Resource>
 */
class ResourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => ResourceType::Circular,
            'title' => fake()->sentence(5),
            'summary' => fake()->sentence(12),
            'body' => fake()->paragraph(),
            'published_on' => fake()->dateTimeBetween('-2 months', '-10 days'),
            'status' => PublicationStatus::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => PublicationStatus::Draft]);
    }

    public function supplies(string $grade, int $year = 2027): static
    {
        return $this->state(fn () => [
            'type' => ResourceType::Supplies,
            'title' => "Lista de útiles {$grade} {$year}",
            'grade' => $grade,
            'school_year' => $year,
        ]);
    }

    public function ofType(ResourceType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }
}
