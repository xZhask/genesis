<?php

namespace Tests\Feature;

use App\Enums\ResourceType;
use App\Models\DonationAccount;
use App\Models\Donor;
use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\Post;
use App\Models\Resource;
use App\Models\Testimonial;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Revisión automática de accesibilidad básica en todas las páginas públicas,
 * con contenido cargado: un solo h1, alt en las imágenes, ids únicos y
 * etiqueta en cada campo de formulario.
 */
class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Post::factory()->create(['title' => 'Noticia']);
        Event::factory()->create(['starts_at' => now()->addDays(3)]);
        $album = GalleryAlbum::factory()->create(['title' => 'Album']);
        GalleryPhoto::factory()->count(3)->for($album, 'album')->create();
        Resource::factory()->create();
        Resource::factory()->supplies('3.°')->create();
        Resource::factory()->ofType(ResourceType::Uniform)->create(['image_path' => 'resources/x', 'image_alt' => 'Uniforme']);
        Resource::factory()->ofType(ResourceType::Schedule)->create();
        DonationAccount::factory()->create();
        Donor::factory()->create();
        Testimonial::factory()->create();
    }

    public static function pages(): array
    {
        return [
            'inicio' => ['/'],
            'nosotros' => ['/nosotros'],
            'admisiones' => ['/admisiones'],
            'calendario' => ['/calendario'],
            'calendario mes' => ['/calendario?vista=mes'],
            'noticias' => ['/noticias'],
            'noticia' => ['/noticias/noticia'],
            'galería' => ['/galeria'],
            'álbum' => ['/galeria/album'],
            'recursos' => ['/recursos'],
            'circulares' => ['/recursos/circulares'],
            'apóyanos' => ['/apoyanos'],
            'política de datos' => ['/politica-de-datos'],
            'ingresar' => ['/ingresar'],
            'recuperar contraseña' => ['/recuperar-contrasena'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_meets_basic_accessibility(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $this->assertSame('es-CO', $dom->documentElement->getAttribute('lang'));
        $this->assertSame(1, $xpath->query('//h1')->length, 'Debe haber exactamente un h1');

        foreach ($xpath->query('//img') as $img) {
            $this->assertTrue($img->hasAttribute('alt'), 'Imagen sin alt: '.$img->getAttribute('src'));
        }

        $ids = [];
        foreach ($xpath->query('//*[@id]') as $node) {
            $ids[] = $node->getAttribute('id');
        }
        $this->assertSame([], array_values(array_diff_assoc($ids, array_unique($ids))), 'ids repetidos');

        $fields = $xpath->query('//input[not(@type="hidden") and not(@type="submit")] | //select | //textarea');
        foreach ($fields as $field) {
            $id = $field->getAttribute('id');
            $labelled = $field->hasAttribute('aria-label')
                || ($id && $xpath->query("//label[@for='{$id}']")->length)
                || $xpath->query('ancestor::label', $field)->length;

            $this->assertTrue((bool) $labelled, 'Campo sin etiqueta: '.($field->getAttribute('name') ?: $id));
        }

        // Los enlaces que abren otra pestaña no deben exponer window.opener
        foreach ($xpath->query('//a[@target="_blank"]') as $link) {
            $this->assertStringContainsString('noopener', $link->getAttribute('rel'), 'Falta rel=noopener: '.$link->getAttribute('href'));
        }
    }
}
