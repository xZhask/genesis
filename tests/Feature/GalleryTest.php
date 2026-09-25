<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function albumWithPhotos(array $attributes = [], int $photos = 2): GalleryAlbum
    {
        $album = GalleryAlbum::factory()->create($attributes);
        GalleryPhoto::factory()->count($photos)->for($album, 'album')->create();

        return $album;
    }

    public function test_index_lists_only_published_albums_with_photos(): void
    {
        $this->albumWithPhotos(['title' => 'Día de la familia', 'taken_on' => '2026-09-13']);
        $this->albumWithPhotos(['title' => 'Álbum en borrador'])->update(['status' => 'draft']);
        GalleryAlbum::factory()->create(['title' => 'Álbum vacío']);

        $this->get(route('gallery'))
            ->assertOk()
            ->assertSee('Día de la familia')
            ->assertSee('13 de septiembre de 2026')
            ->assertSee('2 fotos')
            ->assertDontSee('Álbum en borrador')
            ->assertDontSee('Álbum vacío');
    }

    public function test_albums_are_ordered_by_activity_date(): void
    {
        $this->albumWithPhotos(['title' => 'Actividad antigua', 'taken_on' => '2026-06-01']);
        $this->albumWithPhotos(['title' => 'Actividad reciente', 'taken_on' => '2026-09-01']);

        $this->get(route('gallery'))->assertSeeInOrder(['Actividad reciente', 'Actividad antigua']);
    }

    public function test_album_shows_photos_in_order_with_lightbox(): void
    {
        $album = GalleryAlbum::factory()->create(['title' => 'Feria de ciencias']);
        GalleryPhoto::factory()->for($album, 'album')->create(['alt' => 'Segunda foto', 'position' => 2]);
        GalleryPhoto::factory()->for($album, 'album')->create(['alt' => 'Primera foto', 'position' => 1, 'caption' => 'Pie visible']);
        GalleryPhoto::factory()->for($album, 'album')->create(['alt' => null, 'position' => 3]);

        $this->get(route('gallery.show', $album))
            ->assertOk()
            ->assertSee('data-gallery', false)
            ->assertSee('data-lightbox', false)
            ->assertSeeInOrder(['Primera foto', 'Segunda foto', 'Foto de Feria de ciencias'])
            ->assertSee('data-caption="Pie visible"', false)
            ->assertSee('https://wa.me/?text='.rawurlencode('Feria de ciencias '.$album->url()), false);
    }

    public function test_drafts_and_empty_albums_are_404_for_public_and_previewable_by_admin(): void
    {
        $draft = $this->albumWithPhotos()->fresh();
        $draft->update(['status' => 'draft']);
        $empty = GalleryAlbum::factory()->create();

        foreach ([$draft, $empty] as $album) {
            $this->get(route('gallery.show', $album))->assertNotFound();
            $this->actingAs(User::factory()->create())->get(route('gallery.show', $album))->assertNotFound();
            $this->actingAs(User::factory()->admin()->create())
                ->get(route('gallery.show', $album))
                ->assertOk()
                ->assertSee('Vista previa');
            auth()->logout();
        }
    }

    public function test_home_shows_latest_published_photos(): void
    {
        $this->albumWithPhotos(['title' => 'Álbum publicado'], 1);
        $hidden = GalleryAlbum::factory()->draft()->create(['title' => 'Oculto']);
        GalleryPhoto::factory()->for($hidden, 'album')->create(['alt' => 'Foto de borrador']);

        $this->get(route('home'))
            ->assertSee('Momentos Génesis')
            ->assertSee('data-caption="Álbum publicado"', false)
            ->assertSee('-600.webp', false)
            ->assertDontSee('Foto de borrador');
    }

    public function test_home_shows_at_most_eight_photos(): void
    {
        $this->albumWithPhotos([], 10);

        $html = $this->get(route('home'))->getContent();

        $this->assertSame(8, substr_count($html, 'aria-label="Ver foto:'));
    }
}
