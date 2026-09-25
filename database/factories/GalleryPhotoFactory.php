<?php

namespace Database\Factories;

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Foto sin archivo real (para tests de permisos y listados).
 *
 * @extends Factory<GalleryPhoto>
 */
class GalleryPhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gallery_album_id' => GalleryAlbum::factory(),
            'path' => 'gallery/'.fake()->uuid(),
            'alt' => fake()->sentence(4),
            'caption' => null,
            'width' => 1200,
            'height' => 800,
            'position' => fake()->unique()->numberBetween(1, 100000),
        ];
    }
}
