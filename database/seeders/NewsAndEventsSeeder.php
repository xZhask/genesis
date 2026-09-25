<?php

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Models\Event;
use App\Models\Post;
use App\Models\User;
use App\Support\ImageResizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Noticias y eventos de ejemplo (solo desarrollo local). Las fechas se calculan
 * a partir de hoy para que el calendario siempre tenga eventos próximos.
 */
class NewsAndEventsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@genesis.test')->first();

        $posts = [
            // Portadas: imágenes provisionales de public/demo (no van a producción)
            ['Nuestra feria de ciencias llenó el colegio de ideas', 'Estudiantes de primaria y secundaria presentaron sus proyectos ante las familias y jurados invitados.', 7,
                'dia-ciencias.webp', 'Estudiantes muestran sus proyectos de ciencias a otros compañeros en el patio del colegio'],
            ['Transición celebró el Día del Amor y la Amistad', 'Una mañana de juegos, cartas y compartir en familia.', 13,
                'dia-amistad-amor.webp', 'Niños y niñas comparten cartas y juegos en la celebración del Día del Amor y la Amistad'],
            ['Así vivimos la semana del idioma', 'Lecturas en voz alta, cuentos ilustrados y una feria del libro hecha por los niños.', 20,
                'dia-idioma.webp', 'Estudiantes leen libros junto a una tarima decorada para el Día del Idioma'],
            ['Salida pedagógica de primaria a la ribera del río Magdalena', 'Los estudiantes de 4.° y 5.° aprendieron sobre el cuidado del agua y la fauna de nuestra región.', 34, null, null],
            ['Taller para familias: acompañar las tareas en casa', 'Compartimos estrategias sencillas para crear hábitos de estudio con los niños.', 45, null, null],
        ];

        foreach ($posts as [$title, $excerpt, $daysAgo, $cover, $alt]) {
            $post = new Post([
                'title' => $title,
                'excerpt' => $excerpt,
                'body' => "{$excerpt}\n\nDurante la jornada, los docentes acompañaron a cada grupo y las familias participaron activamente. **Gracias a todos** por hacer posible esta actividad.\n\n- Participaron estudiantes de varios grados.\n- Las familias fueron parte fundamental.\n\nPronto compartiremos más fotos en la galería.",
                'status' => PostStatus::Published,
                'published_at' => now()->subDays($daysAgo)->setTime(9, 0),
            ]);
            $post->author_id = $admin?->id;
            if ($cover && is_file($file = public_path("demo/{$cover}"))) {
                $post->cover_path = ImageResizer::store(new UploadedFile($file, $cover, 'image/webp', null, true), 'posts');
                $post->cover_alt = $alt;
            }
            $post->save();
        }

        $draft = new Post([
            'title' => 'Borrador: preparativos de la clausura',
            'excerpt' => 'Noticia en preparación (no se ve en la web).',
            'body' => 'Texto en preparación.',
            'status' => PostStatus::Draft,
        ]);
        $draft->author_id = $admin?->id;
        $draft->save();

        $day = fn (int $days, string $time = '07:00') => Carbon::parse(now()->addDays($days)->toDateString().' '.$time);

        $events = [
            ['Entrega de boletines del tercer periodo', $day(4), $day(4, '10:00'), false, 'Aulas de cada grado', null, 'Asiste con tu acudido. Si no puedes venir, avisa al director de grupo.'],
            ['Semana de receso escolar', $day(10)->startOfDay(), $day(14)->startOfDay(), true, null, null, null],
            ['Reunión de acudientes de preescolar', $day(17, '15:00'), $day(17, '16:30'), false, 'Salón de preescolar', 'preschool', null],
            ['Jornada de oración en familia', $day(22, '08:00'), null, false, 'Patio central', null, null],
            ['Izada de bandera de primaria', $day(25, '07:00'), null, false, 'Patio central', 'primary', null],
            ['Feria de proyectos de secundaria', $day(31, '08:00'), $day(31, '12:00'), false, 'Aulas de secundaria', 'secondary', 'Presentación de proyectos ambientales y científicos.'],
            ['Día de la familia Génesis', $day(40)->startOfDay(), null, true, 'Colegio', null, null],
            ['Clausura y grados de Transición', $day(55, '09:00'), null, false, 'Patio central', 'preschool', null],
        ];

        foreach ($events as [$title, $start, $end, $allDay, $location, $level, $description]) {
            $event = new Event([
                'title' => $title,
                'starts_at' => $start,
                'ends_at' => $end,
                'all_day' => $allDay,
                'location' => $location,
                'level' => $level,
                'description' => $description,
            ]);
            $event->created_by = $admin?->id;
            $event->save();
        }
    }
}
