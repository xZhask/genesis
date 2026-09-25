<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\DonationAccount;
use App\Models\Donor;
use App\Models\Event;
use App\Models\GalleryPhoto;
use App\Models\Post;
use App\Models\Resource;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\VolunteerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Solo el admin gestiona el contenido de la web: noticias, eventos, galería,
 * recursos y Apóyanos (regla de seguridad 2).
 */
class ContentAccessTest extends TestCase
{
    use RefreshDatabase;

    /** Un registro de cada tipo, para probar las URL de edición y borrado. */
    private function records(): array
    {
        $photo = GalleryPhoto::factory()->create();

        return [
            'post' => Post::factory()->create(),
            'event' => Event::factory()->create(),
            'photo' => $photo,
            'album' => $photo->album,
            'resource' => Resource::factory()->create(),
            'account' => DonationAccount::factory()->create(),
            'donor' => Donor::factory()->create(),
            'testimonial' => Testimonial::factory()->create(),
            'volunteer' => VolunteerApplication::factory()->create(),
        ];
    }

    private function requests(array $r): array
    {
        $crud = fn (string $name, $model) => [
            ['GET', route("admin.{$name}.index")],
            ['GET', route("admin.{$name}.create")],
            ['POST', route("admin.{$name}.store")],
            ['GET', route("admin.{$name}.edit", $model)],
            ['PUT', route("admin.{$name}.update", $model)],
            ['DELETE', route("admin.{$name}.destroy", $model)],
        ];

        return [
            ...$crud('posts', $r['post']),
            ...$crud('events', $r['event']),
            ...$crud('albums', $r['album']),
            ['POST', route('admin.albums.photos.store', $r['album'])],
            ['PUT', route('admin.albums.photos.update', $r['album'])],
            ...$crud('resources', $r['resource']),
            ...$crud('accounts', $r['account']),
            ...$crud('donors', $r['donor']),
            ...$crud('testimonials', $r['testimonial']),
            ['GET', route('admin.volunteers.index')],
            ['GET', route('admin.volunteers.show', $r['volunteer'])],
            ['PUT', route('admin.volunteers.update', $r['volunteer'])],
        ];
    }

    public static function nonAdminRoles(): array
    {
        return [
            'docente' => [Role::Teacher],
            'estudiante' => [Role::Student],
            'acudiente' => [Role::Guardian],
        ];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_roles_get_403(Role $role): void
    {
        $records = $this->records();
        $user = User::factory()->role($role)->create();

        foreach ($this->requests($records) as [$method, $url]) {
            $this->actingAs($user)
                ->call($method, $url, ['title' => 'Intento', 'status' => 'archived', 'do' => "delete:{$records['photo']->id}"])
                ->assertForbidden();
        }

        foreach ($records as $model) {
            $this->assertModelExists($model);
        }
        $this->assertSame(1, Post::count());
        $this->assertNotSame('archived', $records['volunteer']->fresh()->status->value);
    }

    public function test_guests_are_sent_to_login(): void
    {
        foreach ($this->requests($this->records()) as [$method, $url]) {
            $this->call($method, $url)->assertRedirect(route('login'));
        }
    }
}
