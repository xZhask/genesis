<?php

namespace Tests\Feature\Portal;

use App\Enums\ContactRequestStatus;
use App\Enums\Role;
use App\Models\ContactUpdateRequest;
use App\Models\Guardian;
use App\Models\User;

/**
 * El acudiente pide cambiar su teléfono o su correo; el dato no cambia
 * hasta que el admin lo aprueba (regla del alcance de la fase 2).
 */
class ContactUpdateRequestTest extends PortalTestCase
{
    private User $guardianUser;

    private Guardian $guardian;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guardianUser = $this->guardianOf($this->andres);
        $this->guardian = $this->guardianUser->guardian;
        $this->guardian->update(['phone' => '321 000 0000', 'email' => 'marta@example.com']);
        $this->guardianUser->forceFill(['email' => 'marta@example.com'])->save();
        $this->admin = User::factory()->admin()->create();
    }

    private function send(array $data, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->guardianUser)->post(route('portal.guardian.contact.store'), $data);
    }

    public function test_guardian_requests_a_change_and_the_data_does_not_change(): void
    {
        $this->actingAs($this->guardianUser)->get(route('portal.guardian.contact'))
            ->assertOk()->assertSee('321 000 0000')->assertSee('marta@example.com');

        $this->send(['phone' => '300 123 4567', 'email' => 'marta@example.com'])
            ->assertRedirect(route('portal.guardian.contact'))->assertSessionHasNoErrors();

        $this->assertSame('321 000 0000', $this->guardian->fresh()->phone);
        $request = ContactUpdateRequest::sole(); // el correo no cambió: no se crea solicitud
        $this->assertSame('321 000 0000', $request->old_value);
        $this->assertSame('300 123 4567', $request->new_value);

        $this->actingAs($this->guardianUser)->get(route('portal.guardian.contact'))
            ->assertSee('En revisión')->assertSee('Pediste cambiarlo por');
    }

    public function test_a_new_request_replaces_the_pending_one(): void
    {
        $this->send(['phone' => '300 123 4567']);
        $this->send(['phone' => '300 765 4321']);
        $this->assertSame('300 765 4321', ContactUpdateRequest::sole()->new_value);

        // Reenviar el formulario tal como está no crea otra
        $this->send(['phone' => '300 765 4321']);
        $this->assertSame(1, ContactUpdateRequest::count());

        // Volver al dato actual cancela la pendiente
        $this->send(['phone' => '321 000 0000']);
        $this->assertSame(0, ContactUpdateRequest::count());
    }

    public function test_invalid_data_and_emails_of_other_accounts_are_rejected(): void
    {
        User::factory()->create(['email' => 'ocupado@example.com']);

        $this->send(['phone' => 'llámame', 'email' => 'no-es-correo'])->assertSessionHasErrors(['phone', 'email']);
        $this->send(['email' => 'OCUPADO@example.com'])->assertSessionHasErrors('email');
        $this->assertSame(0, ContactUpdateRequest::count());
    }

    public function test_admin_approves_and_the_email_also_changes_in_the_account(): void
    {
        $this->send(['phone' => '300 123 4567', 'email' => 'Marta.Nueva@example.com']);

        $this->actingAs($this->admin)->get(route('admin.people.contact-requests.index'))
            ->assertOk()
            ->assertSee($this->guardian->fullName())
            ->assertSee('Andrés Pérez Díaz')
            ->assertSee('marta.nueva@example.com')
            ->assertSee('también cambia el correo de su cuenta');

        foreach (ContactUpdateRequest::all() as $request) {
            $this->actingAs($this->admin)->post(route('admin.people.contact-requests.approve', $request))->assertRedirect();
        }

        $this->assertSame('300 123 4567', $this->guardian->fresh()->phone);
        $this->assertSame('marta.nueva@example.com', $this->guardian->fresh()->email);
        $this->assertSame('marta.nueva@example.com', $this->guardianUser->fresh()->email);
        $this->assertSame(2, ContactUpdateRequest::where('status', ContactRequestStatus::Approved)->where('reviewed_by', $this->admin->id)->count());

        // Aprobar de nuevo no hace nada
        $this->guardian->update(['phone' => '311 222 3333']);
        $this->actingAs($this->admin)->post(route('admin.people.contact-requests.approve', ContactUpdateRequest::first()));
        $this->assertSame('311 222 3333', $this->guardian->fresh()->phone);
    }

    public function test_approval_fails_if_the_email_was_taken_meanwhile(): void
    {
        $this->send(['email' => 'nuevo@example.com']);
        User::factory()->create(['email' => 'nuevo@example.com']);

        $this->actingAs($this->admin)->post(route('admin.people.contact-requests.approve', ContactUpdateRequest::sole()))
            ->assertSessionHasErrors('request');

        $this->assertSame('marta@example.com', $this->guardian->fresh()->email);
        $this->assertTrue(ContactUpdateRequest::sole()->isPending());
    }

    public function test_admin_rejects_with_a_reason_the_guardian_sees(): void
    {
        $this->send(['phone' => '300 123 4567']);

        $this->actingAs($this->admin)->post(route('admin.people.contact-requests.reject', ContactUpdateRequest::sole()), [
            'note' => 'Llámanos para confirmar el número.',
        ])->assertRedirect();

        $this->assertSame('321 000 0000', $this->guardian->fresh()->phone);
        $this->actingAs($this->guardianUser)->get(route('portal.guardian.contact'))
            ->assertSee('No aprobada')->assertSee('Llámanos para confirmar el número.');
    }

    public function test_stale_requests_are_flagged(): void
    {
        $this->send(['phone' => '300 123 4567']);
        $this->guardian->update(['phone' => '311 222 3333']); // el admin lo editó en la ficha

        $this->actingAs($this->admin)->get(route('admin.people.contact-requests.index'))
            ->assertSee('ya no es el que tenía cuando se pidió');
    }

    public function test_only_guardians_request_and_only_admins_review(): void
    {
        $this->send(['phone' => '300 123 4567']);
        $request = ContactUpdateRequest::sole();

        // Estudiante y docente sin vínculo de acudiente no tienen «Mis datos»
        $student = User::factory()->role(Role::Student)->create();
        $this->actingAs($student)->get(route('portal.guardian.contact'))->assertForbidden();
        $this->send(['phone' => '300 000 0000'], $student)->assertForbidden();
        $this->actingAs($this->teacher)->get(route('portal.guardian.contact'))->assertForbidden();

        // Ni acudientes ni docentes ni estudiantes revisan solicitudes
        foreach ([$this->guardianUser, $this->guardianOf($this->lucia), $this->teacher, $student] as $user) {
            $this->actingAs($user)->get(route('admin.people.contact-requests.index'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.people.contact-requests.approve', $request))->assertForbidden();
            $this->actingAs($user)->post(route('admin.people.contact-requests.reject', $request))->assertForbidden();
        }

        $this->assertTrue($request->fresh()->isPending());
        $this->assertSame('321 000 0000', $this->guardian->fresh()->phone);
    }

    public function test_each_guardian_only_sees_their_own_requests(): void
    {
        $this->send(['phone' => '300 123 4567']);
        $other = $this->guardianOf($this->lucia);

        $this->actingAs($other)->get(route('portal.guardian.contact'))
            ->assertOk()->assertDontSee('300 123 4567')->assertDontSee('En revisión');
    }

    public function test_pending_count_shows_in_the_admin(): void
    {
        $this->send(['phone' => '300 123 4567', 'email' => 'otro@example.com']);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertSee('2 cambios de contacto')
            ->assertSee('nav-count', false);
    }
}
