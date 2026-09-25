<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\AdmissionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regla de seguridad 2 (CLAUDE.md): nadie que no sea admin entra al panel,
 * ni siquiera escribiendo la URL a mano.
 */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /** @return array<string, array{0: string, 1: string}> */
    private function adminRequests(AdmissionRequest $admission): array
    {
        return [
            ['GET', route('admin.dashboard')],
            ['GET', route('admin.admissions.index')],
            ['GET', route('admin.admissions.show', $admission)],
            ['PUT', route('admin.admissions.status', $admission)],
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
    public function test_non_admin_roles_get_403_on_every_admin_url(Role $role): void
    {
        $admission = AdmissionRequest::factory()->create();
        $user = User::factory()->role($role)->create();

        foreach ($this->adminRequests($admission) as [$method, $url]) {
            $this->actingAs($user)
                ->call($method, $url, ['status' => 'accepted'])
                ->assertForbidden();
        }

        $this->assertSame('received', $admission->fresh()->status->value);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $admission = AdmissionRequest::factory()->create();

        foreach ($this->adminRequests($admission) as [$method, $url]) {
            $this->call($method, $url)->assertRedirect(route('login'));
        }
    }

    public function test_inactive_admin_is_forbidden(): void
    {
        $this->actingAs(User::factory()->admin()->inactive()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_open_every_admin_page(): void
    {
        $admission = AdmissionRequest::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.admissions.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.admissions.show', $admission))->assertOk();
    }
}
