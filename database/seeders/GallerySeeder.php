<?php

namespace Database\Seeders;

use App\Enums\AlbumStatus;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Support\ImageResizer;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Álbumes de ejemplo (solo desarrollo local) con las fotos provisionales del
 * mockup (public/demo). En producción no se ejecuta db:seed.
 */
class GallerySeeder extends Seeder
{
    public function run(): void
    {
        $albums = [
            ['Día de la familia Génesis', 'Juegos, música y un almuerzo compartido entre familias, estudiantes y docentes.', 12, AlbumStatus::Published, [
                ['patio.jpg', 'Familias y estudiantes reunidos en el patio del colegio', 'Juegos en el patio'],
                ['feria.jpg', 'Estudiantes mostrando sus trabajos a los acudientes', null],
                ['devocional.jpg', 'Momento de oración con las familias', 'Oración de apertura'],
                ['aula.jpg', null, null],
            ]],
            ['Feria de ciencias', 'Los estudiantes de primaria y secundaria presentaron sus proyectos.', 26, AlbumStatus::Published, [
                ['feria.jpg', 'Estudiantes de secundaria explicando su proyecto', null],
                ['aula.jpg', 'Grupo de primaria preparando su experimento en el aula', null],
            ]],
            ['Izada de bandera de septiembre', null, 40, AlbumStatus::Published, [
                ['devocional.jpg', 'Estudiantes formados durante la izada de bandera', null],
                ['patio.jpg', 'Patio central durante el acto cívico', null],
            ]],
            ['Preparativos de la clausura', 'Álbum en preparación (no se ve en la web).', 2, AlbumStatus::Draft, [
                ['aula.jpg', null, null],
            ]],
        ];

        foreach ($albums as [$title, $description, $daysAgo, $status, $photos]) {
            $album = GalleryAlbum::create([
                'title' => $title,
                'description' => $description,
                'taken_on' => today()->subDays($daysAgo),
                'status' => $status,
            ]);

            foreach ($photos as $position => [$file, $alt, $caption]) {
                $path = public_path("demo/{$file}");
                if (! is_file($path)) {
                    continue;
                }

                $image = ImageResizer::save(new UploadedFile($path, $file, 'image/jpeg', null, true), 'gallery');

                $photo = new GalleryPhoto(['alt' => $alt, 'caption' => $caption]);
                $photo->gallery_album_id = $album->id;
                $photo->path = $image['path'];
                $photo->width = $image['width'];
                $photo->height = $image['height'];
                $photo->position = $position + 1;
                $photo->save();
            }
        }
    }
}
