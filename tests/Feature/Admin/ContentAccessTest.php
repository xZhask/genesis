<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Event;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Solo el admin gestiona noticias y eventos (regla de seguridad 2). */
class ContentAccessTest extends TestCase
{
    use RefreshDatabase;

    private function requests(Post $post, Event $event): array
    {
        return [
            ['GET', route('admin.posts.index')],
            ['GET', route('admin.posts.create')],
            ['POST', route('admin.posts.store')],
            ['GET', route('admin.posts.edit', $post)],
            ['PUT', route('admin.posts.update', $post)],
            ['DELETE', route('admin.posts.destroy', $post)],
            ['GET', route('admin.events.index')],
            ['GET', route('admin.events.create')],
            ['POST', route('admin.events.store')],
            ['GET', route('admin.events.edit', $event)],
            ['PUT', route('admin.events.update', $event)],
            ['DELETE', route('admin.events.destroy', $event)],
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
        $post = Post::factory()->create();
        $event = Event::factory()->create();
        $user = User::factory()->role($role)->create();

        foreach ($this->requests($post, $event) as [$method, $url]) {
            $this->actingAs($user)->call($method, $url, ['title' => 'Intento'])->assertForbidden();
        }

        $this->assertModelExists($post);
        $this->assertModelExists($event);
        $this->assertSame(1, Post::count());
    }

    public function test_guests_are_sent_to_login(): void
    {
        foreach ($this->requests(Post::factory()->create(), Event::factory()->create()) as [$method, $url]) {
            $this->call($method, $url)->assertRedirect(route('login'));
        }
    }
}
