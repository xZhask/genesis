<?php

namespace Database\Seeders;

use App\Enums\PublicationStatus;
use App\Enums\ResourceType;
use App\Models\Resource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Recursos de ejemplo (solo desarrollo local). Todo va marcado "de ejemplo":
 * el colegio aún no ha entregado circulares, listas, uniformes ni horarios.
 */
class ResourcesSeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) config('school.admissions.school_year');

        $circulars = [
            ['Circular de ejemplo: entrega de boletines del tercer periodo', 'Fecha, hora y recomendaciones para la reunión con los directores de grupo.', 2, true],
            ['Circular de ejemplo: jornada de vacunación en el colegio', 'La secretaría de salud visitará el colegio. Revisa el carné de vacunas de tu acudido.', 9, false],
            ['Circular de ejemplo: semana de receso escolar', 'No habrá clases del lunes al viernes. Las clases se reanudan el lunes siguiente.', 21, false],
        ];

        foreach ($circulars as [$title, $summary, $daysAgo, $withPdf]) {
            $resource = new Resource([
                'type' => ResourceType::Circular,
                'title' => $title,
                'summary' => $summary,
                'body' => $withPdf ? null : "Apreciadas familias:\n\n{$summary}\n\nGracias por su acompañamiento.\n\n**Rectoría**",
                'published_on' => today()->subDays($daysAgo),
                'status' => PublicationStatus::Published,
            ]);

            if ($withPdf) {
                $resource->file_path = 'resources/'.Str::uuid().'.pdf';
                $resource->file_name = 'circular-de-ejemplo.pdf';
                $pdf = $this->samplePdf();
                Storage::disk('public')->put($resource->file_path, $pdf);
                $resource->file_size = strlen($pdf);
            }

            $resource->save();
        }

        Resource::create([
            'type' => ResourceType::Circular,
            'title' => 'Borrador: circular de clausura',
            'body' => 'En preparación (no se ve en la web).',
            'published_on' => today(),
            'status' => PublicationStatus::Draft,
        ]);

        $lists = [
            'Transición' => "- 2 cuadernos grandes de cuadritos\n- 1 caja de colores\n- 1 tijera punta roma\n- 1 pegante en barra\n- 1 cartuchera marcada",
            '3.°' => "- 5 cuadernos cosidos de 100 hojas\n- 1 diccionario español-inglés\n- Lápiz, borrador y sacapuntas\n- 1 regla de 30 cm\n- Colores",
            '7.°' => "- 8 cuadernos de 100 hojas\n- 1 calculadora básica\n- Juego de geometría\n- 1 Biblia\n- Bata de laboratorio",
        ];

        foreach ($lists as $grade => $body) {
            Resource::create([
                'type' => ResourceType::Supplies,
                'title' => "Lista de útiles {$grade} {$year} (ejemplo)",
                'summary' => 'Lista de ejemplo: el colegio publicará la lista real.',
                'body' => $body,
                'grade' => $grade,
                'school_year' => $year,
                'published_on' => today()->subDays(15),
                'status' => PublicationStatus::Published,
            ]);
        }

        $uniforms = [
            ['Uniforme de diario (ejemplo)', "**Niñas:** jardinera azul, camisa blanca, medias blancas y zapatos negros.\n\n**Niños:** pantalón azul, camisa blanca, medias azules y zapatos negros.\n\n**Dónde conseguirlo:** proveedor de ejemplo, teléfono por confirmar."],
            ['Uniforme de educación física (ejemplo)', "Sudadera azul con el escudo del colegio, camiseta blanca y tenis blancos.\n\n**Dónde conseguirlo:** proveedor de ejemplo, teléfono por confirmar."],
        ];

        foreach ($uniforms as $i => [$title, $body]) {
            Resource::create([
                'type' => ResourceType::Uniform,
                'title' => $title,
                'body' => $body,
                'published_on' => today()->subDays(30),
                'status' => PublicationStatus::Published,
                'position' => $i + 1,
            ]);
        }

        Resource::create([
            'type' => ResourceType::Schedule,
            'title' => 'Jornada escolar (ejemplo)',
            'summary' => 'Horarios de ejemplo: el colegio confirmará los reales.',
            'body' => "| Nivel | Entrada | Salida |\n|---|---|---|\n| Preescolar | 7:00 a. m. | 11:30 a. m. |\n| Primaria | 6:45 a. m. | 12:30 p. m. |\n| Secundaria | 6:45 a. m. | 1:00 p. m. |",
            'published_on' => today()->subDays(30),
            'status' => PublicationStatus::Published,
            'position' => 1,
        ]);
    }

    /** PDF mínimo válido de una página, para probar la descarga en local. */
    private function samplePdf(): string
    {
        $text = 'Documento de ejemplo';
        $stream = "BT /F1 18 Tf 72 720 Td ({$text}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
