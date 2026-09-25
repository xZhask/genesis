<?php

namespace Tests\Feature\Admin;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostManagementTest extends TestCase
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

    private function data(array $overrides = []): array
    {
        return [
            'title' => 'Feria de ciencias',
            'excerpt' => 'Los estudiantes presentaron sus proyectos.',
            'body' => "Primer párrafo.\n\nSegundo **párrafo**.",
            'status' => 'published',
            'published_at' => '',
            ...$overrides,
        ];
    }

    public function test_admin_publishes_a_post_now_when_no_date_is_given(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.posts.store'), $this->data())
            ->assertRedirect(route('admin.posts.index'))
            ->assertSessionHas('status_message', 'Se publicó «Feria de ciencias».');

        $post = Post::sole();
        $this->assertSame('feria-de-ciencias', $post->slug);
        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertTrue($post->isVisible());
        $this->assertSame($this->admin->id, $post->author_id);
    }

    public function test_drafts_and_scheduled_posts_are_saved_but_not_public(): void
    {
        $this->actingAs($this->admin)->post(route('admin.posts.store'), $this->data(['title' => 'Borrador', 'status' => 'draft']));
        $this->actingAs($this->admin)
            ->post(route('admin.posts.store'), $this->data(['title' => 'Programada', 'published_at' => now()->addDays(2)->format('Y-m-d\TH:i')]))
            ->assertSessionHas('status_message', fn ($m) => str_contains($m, 'se publicará el'));

        $this->assertSame(0, Post::published()->count());
        $this->assertSame(2, Post::count());
    }

    public function test_same_title_gets_a_different_address(): void
    {
        $this->actingAs($this->admin)->post(route('admin.posts.store'), $this->data());
        $this->actingAs($this->admin)->post(route('admin.posts.store'), $this->data());

        $this->assertSame(['feria-de-ciencias', 'feria-de-ciencias-2'], Post::orderBy('id')->pluck('slug')->all());
    }

    public function test_slug_does_not_change_when_title_is_edited(): void
    {
        $post = Post::factory()->create(['title' => 'Título original']);

        $this->actingAs($this->admin)->put(route('admin.posts.update', $post), $this->data(['title' => 'Título corregido']));

        $this->assertSame('titulo-original', $post->fresh()->slug);
        $this->assertSame('Título corregido', $post->fresh()->title);
    }

    public function test_cover_is_resized_and_requires_alt_and_consent(): void
    {
        $photo = UploadedFile::fake()->image('foto.jpg', 2400, 1600);

        $this->actingAs($this->admin)
            ->post(route('admin.posts.store'), $this->data(['cover' => $photo]))
            ->assertSessionHasErrors(['cover_alt', 'minors_consent']);

        $this->actingAs($this->admin)->post(route('admin.posts.store'), $this->data([
            'cover' => UploadedFile::fake()->image('foto.jpg', 2400, 1600),
            'cover_alt' => 'Estudiantes en la feria',
            'minors_consent' => '1',
        ]))->assertSessionHasNoErrors();

        $base = Post::sole()->cover_path;
        foreach ([1200, 600] as $width) {
            Storage::disk('public')->assertExists("{$base}-{$width}.webp");
            $size = getimagesizefromstring(Storage::disk('public')->get("{$base}-{$width}.webp"));
            $this->assertSame($width, $size[0]);
            $this->assertSame((int) round(1600 * $width / 2400), $size[1]);
        }
    }

    public function test_removing_and_deleting_clean_up_image_files(): void
    {
        $this->actingAs($this->admin)->post(route('admin.posts.store'), $this->data([
            'cover' => UploadedFile::fake()->image('foto.jpg', 800, 600),
            'cover_alt' => 'Foto',
            'minors_consent' => '1',
        ]));
        $post = Post::sole();
        $base = $post->cover_path;

        $this->actingAs($this->admin)->put(route('admin.posts.update', $post), $this->data(['remove_cover' => '1']));
        Storage::disk('public')->assertMissing("{$base}-600.webp");
        $this->assertNull($post->fresh()->cover_path);

        $this->actingAs($this->admin)
            ->delete(route('admin.posts.destroy', $post))
            ->assertRedirect(route('admin.posts.index'));
        $this->assertModelMissing($post);
    }

    public function test_validation_messages(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.posts.store'), $this->data(['title' => '', 'excerpt' => '', 'cover' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors(['title' => 'Escribe título.', 'excerpt', 'cover']);
    }

    public function test_admin_pages_render(): void
    {
        $post = Post::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.posts.index'))->assertOk()->assertSee($post->title);
        $this->actingAs($this->admin)->get(route('admin.posts.create'))->assertOk()->assertSee('Nueva noticia');
        $this->actingAs($this->admin)->get(route('admin.posts.edit', $post))->assertOk()->assertSee('Eliminar noticia');
    }
}
