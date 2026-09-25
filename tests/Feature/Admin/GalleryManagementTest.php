<?php

namespace Tests\Feature\Admin;

use App\Enums\AlbumStatus;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
        $this->admin = User::factory()->admin()->create();
    }

    private function upload(GalleryAlbum $album, array $files, bool $consent = true, bool $json = false)
    {
        $data = ['photos' => $files] + ($consent ? ['minors_consent' => '1'] : []);

        return $json
            ? $this->actingAs($this->admin)->postJson(route('admin.albums.photos.store', $album), $data)
            : $this->actingAs($this->admin)->post(route('admin.albums.photos.store', $album), $data);
    }

    public function test_creates_an_album_and_goes_to_upload_photos(): void
    {
        $this->actingAs($this->admin)->post(route('admin.albums.store'), [
            'title' => 'Día de la familia',
            'taken_on' => '2026-09-13',
            'status' => 'published',
        ])->assertRedirect(route('admin.albums.edit', 'dia-de-la-familia'))
            ->assertSessionHas('status_message', 'Se creó el álbum «Día de la familia». Ahora sube las fotos.');

        $album = GalleryAlbum::sole();
        $this->assertSame(AlbumStatus::Published, $album->status);
        $this->assertFalse($album->isVisible(), 'Sin fotos no se ve en la web');
    }

    public function test_album_validation(): void
    {
        $this->actingAs($this->admin)->post(route('admin.albums.store'), [
            'title' => '',
            'taken_on' => now()->addDays(3)->toDateString(),
            'status' => 'otro',
        ])->assertSessionHasErrors(['title', 'taken_on' => 'La fecha de la actividad no puede ser futura.', 'status']);

        $this->assertSame(0, GalleryAlbum::count());
    }

    public function test_uploads_photos_in_two_sizes_with_dimensions(): void
    {
        $album = GalleryAlbum::factory()->create();

        $this->upload($album, [
            UploadedFile::fake()->image('uno.jpg', 2400, 1600),
            UploadedFile::fake()->image('dos.png', 800, 1000),
        ])->assertRedirect(route('admin.albums.edit', ['album' => $album, 'subidas' => 2]).'#fotos');

        [$first, $second] = $album->photos()->get();

        $this->assertSame([1200, 800, 1], [$first->width, $first->height, $first->position]);
        $this->assertSame([800, 1000, 2], [$second->width, $second->height, $second->position]);
        $this->assertNull($first->alt);
        $this->assertSame('Foto de '.$album->title, $first->altText());

        foreach ([1200, 600] as $width) {
            Storage::disk('public')->assertExists("{$first->path}-{$width}.webp");
        }
    }

    public function test_upload_one_by_one_answers_json(): void
    {
        $album = GalleryAlbum::factory()->create();

        $this->upload($album, [UploadedFile::fake()->image('uno.jpg', 800, 600)], json: true)
            ->assertCreated()
            ->assertJson(['saved' => 1]);

        $this->upload($album, [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')], json: true)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photos.0' => 'Solo se aceptan fotos JPG, PNG o WebP.']);

        $this->assertSame(1, $album->photos()->count());
    }

    public function test_upload_requires_consent_and_size_limit(): void
    {
        $album = GalleryAlbum::factory()->create();

        $this->upload($album, [UploadedFile::fake()->image('uno.jpg')], consent: false)
            ->assertSessionHasErrors(['minors_consent' => 'Confirma que tienes la autorización de los acudientes para publicar las fotos.']);

        $this->upload($album, [UploadedFile::fake()->image('grande.jpg')->size(11000)])
            ->assertSessionHasErrors(['photos.0' => 'La foto pesa más de 10 MB. Usa una más liviana.']);

        $this->upload($album, [])->assertSessionHasErrors(['photos' => 'Elige al menos una foto.']);

        $this->assertSame(0, GalleryPhoto::count());
    }

    public function test_saves_descriptions_of_all_photos_at_once(): void
    {
        $album = GalleryAlbum::factory()->create();
        [$a, $b] = GalleryPhoto::factory()->count(2)->for($album, 'album')->create(['alt' => null]);

        $this->actingAs($this->admin)->put(route('admin.albums.photos.update', $album), [
            'photos' => [
                $a->id => ['alt' => 'Niños en el patio', 'caption' => 'Recreo'],
                $b->id => ['alt' => '', 'caption' => ''],
            ],
        ])->assertRedirect(route('admin.albums.edit', $album).'#fotos');

        $this->assertSame(['Niños en el patio', 'Recreo'], [$a->fresh()->alt, $a->fresh()->caption]);
        $this->assertNull($b->fresh()->alt);
    }

    public function test_moves_photos_and_keeps_descriptions_being_written(): void
    {
        $album = GalleryAlbum::factory()->create();
        $photos = collect([1, 2, 3])->map(fn ($p) => GalleryPhoto::factory()->for($album, 'album')->create(['position' => $p * 10]));

        $this->actingAs($this->admin)->put(route('admin.albums.photos.update', $album), [
            'photos' => [$photos[0]->id => ['alt' => 'Escrita antes de mover']],
            'do' => "up:{$photos[2]->id}",
        ])->assertRedirect(route('admin.albums.edit', $album)."#foto-{$photos[2]->id}");

        $this->assertSame([$photos[0]->id, $photos[2]->id, $photos[1]->id], $album->photos()->pluck('id')->all());
        $this->assertSame('Escrita antes de mover', $photos[0]->fresh()->alt);

        // La primera no puede subir más
        $this->actingAs($this->admin)->put(route('admin.albums.photos.update', $album), ['do' => "up:{$photos[0]->id}"]);
        $this->assertSame($photos[0]->id, $album->photos()->first()->id);
    }

    public function test_sets_cover_and_falls_back_to_first_photo_when_deleted(): void
    {
        $album = GalleryAlbum::factory()->create();
        [$first, $second] = GalleryPhoto::factory()->count(2)->for($album, 'album')->sequence(['position' => 1], ['position' => 2])->create();

        $this->assertTrue($album->cover()->is($first));

        $this->actingAs($this->admin)->put(route('admin.albums.photos.update', $album), ['do' => "cover:{$second->id}"])
            ->assertSessionHas('status_message', 'Se cambió la portada del álbum.');
        $this->assertTrue($album->fresh()->cover()->is($second));

        $this->actingAs($this->admin)->put(route('admin.albums.photos.update', $album), ['do' => "delete:{$second->id}"]);
        $this->assertModelMissing($second);
        $this->assertNull($album->fresh()->cover_photo_id);
        $this->assertTrue($album->fresh()->cover()->is($first));
    }

    public function test_cannot_act_on_a_photo_from_another_album(): void
    {
        $album = GalleryAlbum::factory()->create();
        $other = GalleryPhoto::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.albums.photos.update', $album), ['do' => "delete:{$other->id}"])
            ->assertNotFound();

        $this->assertModelExists($other);
    }

    public function test_deleting_photos_and_albums_removes_files(): void
    {
        $album = GalleryAlbum::factory()->create();
        $this->upload($album, [UploadedFile::fake()->image('uno.jpg'), UploadedFile::fake()->image('dos.jpg')]);
        [$first, $second] = $album->photos()->get();

        $this->actingAs($this->admin)->put(route('admin.albums.photos.update', $album), ['do' => "cover:{$first->id}"]);
        $this->actingAs($this->admin)->put(route('admin.albums.photos.update', $album), ['do' => "delete:{$first->id}"]);
        Storage::disk('public')->assertMissing("{$first->path}-600.webp");
        Storage::disk('public')->assertExists("{$second->path}-600.webp");

        $this->actingAs($this->admin)->delete(route('admin.albums.destroy', $album))
            ->assertRedirect(route('admin.albums.index'));

        $this->assertModelMissing($album);
        $this->assertSame(0, GalleryPhoto::count());
        Storage::disk('public')->assertMissing("{$second->path}-1200.webp");
    }

    public function test_slug_does_not_change_when_title_is_edited(): void
    {
        $album = GalleryAlbum::factory()->create(['title' => 'Título original']);

        $this->actingAs($this->admin)->put(route('admin.albums.update', $album), [
            'title' => 'Título corregido',
            'taken_on' => '2026-09-01',
            'status' => 'draft',
        ])->assertRedirect(route('admin.albums.edit', 'titulo-original'));

        $this->assertSame('Título corregido', $album->fresh()->title);
    }

    public function test_admin_pages_render(): void
    {
        $album = GalleryAlbum::factory()->create(['title' => 'Día de la familia']);
        GalleryPhoto::factory()->for($album, 'album')->create(['alt' => null]);

        $this->actingAs($this->admin)->get(route('admin.albums.index'))
            ->assertOk()->assertSee('Día de la familia')->assertSee('1 sin descripción');
        $this->actingAs($this->admin)->get(route('admin.albums.create'))->assertOk()->assertSee('Nuevo álbum');
        $this->actingAs($this->admin)->get(route('admin.albums.edit', ['album' => $album, 'subidas' => 3]))
            ->assertOk()
            ->assertSee('Se subieron 3 fotos.')
            ->assertSee('1 foto no tiene descripción')
            ->assertSee('Eliminar álbum');
    }
}
