<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Models\GalleryAlbum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryAlbum>
 */
class GalleryAlbumFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['Día de la familia', 'Feria de ciencias', 'Izada de bandera', 'Salida pedagógica']).' '.fake()->unique()->numberBetween(1, 9999),
            'description' => null,
            'taken_on' => fake()->dateTimeBetween('-3 months', '-1 day'),
            'status' => PublicationStatus::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => PublicationStatus::Draft]);
    }
}
