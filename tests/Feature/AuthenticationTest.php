<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_login_page_is_in_spanish_at_ingresar(): void
    {
        $this->assertSame(url('/ingresar'), route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Ingresar al portal')
            ->assertSee('Número de documento')
            ->assertSee('¿Olvidaste tu contraseña?');
    }

    public function test_there_is_no_public_registration(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['email' => 'x@example.com'])->assertNotFound();
    }

    public function test_admin_logs_in_and_lands_on_the_panel(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@genesis.test']);

        $this->post(route('login.store'), ['login' => 'ADMIN@genesis.test', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_other_roles_land_on_the_portal_notice(): void
    {
        $guardian = User::factory()->role(Role::Guardian)->create();

        $this->post(route('login.store'), ['login' => $guardian->email, 'password' => 'password'])
            ->assertRedirect(route('portal'));

        $this->actingAs($guardian)->get(route('portal'))->assertOk()->assertSee('estará disponible muy pronto');
    }

    public function test_wrong_password_shows_spanish_error(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['login' => $user->email, 'password' => 'incorrecta'])
            ->assertSessionHasErrors(['login' => 'El documento (o correo) o la contraseña no son correctos.']);

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_log_in(): void
    {
        $user = User::factory()->admin()->inactive()->create();

        $this->post(route('login.store'), ['login' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_attempts(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['login' => $user->email, 'password' => 'incorrecta']);
        }

        $this->post(route('login.store'), ['login' => $user->email, 'password' => 'password'])
            ->assertStatus(429);

        $this->assertGuest();
    }

    public function test_password_reset_link_is_sent_in_spanish_and_expires(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', 'Si el correo está registrado, te enviamos un enlace para restablecer la contraseña.');

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail->subject === 'Restablece tu contraseña del portal'
                && $mail->viewData['minutes'] === 60
                && str_contains($mail->viewData['url'], '/restablecer-contrasena/');
        });
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Crear contraseña nueva');

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nuevaClave2027',
            'password_confirmation' => 'nuevaClave2027',
        ])->assertRedirect(route('login'));

        $this->post(route('login.store'), ['login' => $user->email, 'password' => 'nuevaClave2027']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_weak_passwords_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.update'), [
            'token' => Password::createToken($user),
            'email' => $user->email,
            'password' => 'corta',
            'password_confirmation' => 'corta',
        ])->assertSessionHasErrors('password');
    }

    public function test_logout(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
