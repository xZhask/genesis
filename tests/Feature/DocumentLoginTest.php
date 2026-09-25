<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Ingreso con número de documento (el correo es opcional) y cambio
 * obligatorio de la contraseña temporal que entrega el colegio.
 */
class DocumentLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_logs_in_with_document_number_even_with_dots_and_spaces(): void
    {
        $guardian = User::factory()->role(Role::Guardian)->create(['document_number' => '1045678901', 'email' => null]);

        $this->post(route('login.store'), ['login' => '1.045.678 901', 'password' => 'password'])
            ->assertRedirect(route('portal.guardian.home'));

        $this->assertAuthenticatedAs($guardian);
        $this->assertNotNull($guardian->fresh()->last_login_at);
    }

    public function test_document_is_stored_normalized(): void
    {
        $user = User::factory()->create(['document_number' => ' 1.045-678 ']);

        $this->assertSame('1045678', $user->fresh()->document_number);
    }

    public function test_staff_can_still_log_in_with_email(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'rectoria@genesis.test', 'document_number' => '22334455']);

        $this->post(route('login.store'), ['login' => 'Rectoria@genesis.test', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_throttle_counts_document_with_and_without_dots_as_the_same_login(): void
    {
        User::factory()->create(['document_number' => '1045678901']);

        foreach (['1.045.678.901', '1045678901', '1 045 678 901', '1045.678.901', '1045678901'] as $attempt) {
            $this->post(route('login.store'), ['login' => $attempt, 'password' => 'incorrecta']);
        }

        $this->post(route('login.store'), ['login' => '1045678901', 'password' => 'password'])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_temporary_password_forces_a_change_before_anything_else(): void
    {
        $admin = User::factory()->admin()->create(['document_number' => '22334455', 'must_change_password' => true]);

        $this->post(route('login.store'), ['login' => '22334455', 'password' => 'password'])
            ->assertRedirect(route('password.change'));

        $this->get(route('admin.dashboard'))->assertRedirect(route('password.change'));
        $this->get(route('admin.people.students.index'))->assertRedirect(route('password.change'));

        $this->get(route('password.change'))
            ->assertOk()
            ->assertSee('Crea tu contraseña')
            ->assertDontSee('Contraseña actual');

        $this->put(route('password.change.update'), ['password' => 'nuevaClave2027', 'password_confirmation' => 'nuevaClave2027'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertFalse($admin->fresh()->must_change_password);
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_temporary_password_also_blocks_the_portal(): void
    {
        $guardian = User::factory()->role(Role::Guardian)->create(['must_change_password' => true]);

        $this->actingAs($guardian)->get(route('portal'))->assertRedirect(route('password.change'));
    }

    public function test_non_admin_with_temporary_password_still_gets_403_on_admin(): void
    {
        $guardian = User::factory()->role(Role::Guardian)->create(['must_change_password' => true]);

        $this->actingAs($guardian)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_new_password_must_differ_from_the_temporary_one_and_be_strong(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)
            ->put(route('password.change.update'), ['password' => 'password', 'password_confirmation' => 'password'])
            ->assertSessionHasErrors('password');

        $this->actingAs($user)
            ->put(route('password.change.update'), ['password' => 'corta1', 'password_confirmation' => 'corta1'])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_voluntary_change_requires_the_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('password.change'))->assertOk()->assertSee('Contraseña actual');

        $this->actingAs($user)
            ->put(route('password.change.update'), ['current_password' => 'otra', 'password' => 'nuevaClave2027', 'password_confirmation' => 'nuevaClave2027'])
            ->assertSessionHasErrors(['current_password' => 'La contraseña actual no es correcta.']);

        $this->actingAs($user)
            ->put(route('password.change.update'), ['current_password' => 'password', 'password' => 'nuevaClave2027', 'password_confirmation' => 'nuevaClave2027'])
            ->assertRedirect(route('portal.guardian.home'));
    }

    public function test_password_change_requires_login(): void
    {
        $this->get(route('password.change'))->assertRedirect(route('login'));
        $this->put(route('password.change.update'))->assertRedirect(route('login'));
    }

    public function test_resetting_by_email_clears_the_temporary_flag(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->post(route('password.update'), [
            'token' => Password::createToken($user),
            'email' => $user->email,
            'password' => 'nuevaClave2027',
            'password_confirmation' => 'nuevaClave2027',
        ])->assertRedirect(route('login'));

        $this->assertFalse($user->fresh()->must_change_password);
    }
}
