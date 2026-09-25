<?php

namespace Tests\Feature\Admin;

use App\Enums\VolunteerStatus;
use App\Models\DonationAccount;
use App\Models\Donor;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\VolunteerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_manages_donation_accounts(): void
    {
        $this->actingAs($this->admin)->post(route('admin.accounts.store'), [
            'label' => 'Bancolombia · Cuenta de ahorros',
            'number' => '  123  456 789 ',
            'holder' => 'Centro Educativo Cristiano Génesis',
            'is_visible' => '1',
        ])->assertRedirect(route('admin.accounts.index'));

        $account = DonationAccount::sole();
        $this->assertSame('123 456 789', $account->number);
        $this->assertTrue($account->is_visible);

        $this->actingAs($this->admin)->put(route('admin.accounts.update', $account), [
            'label' => 'Bancolombia',
            'number' => 'abc',
        ])->assertSessionHasErrors(['number' => 'El número solo puede tener dígitos, espacios, puntos o guiones.']);

        // Sin marcar "Mostrar en la web" queda oculta
        $this->actingAs($this->admin)->put(route('admin.accounts.update', $account), ['label' => 'Bancolombia', 'number' => '123']);
        $this->assertFalse($account->fresh()->is_visible);

        $this->actingAs($this->admin)->delete(route('admin.accounts.destroy', $account));
        $this->assertModelMissing($account);
    }

    public function test_donor_needs_consent_once_and_it_is_recorded(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.donors.store'), ['name' => 'Ferretería El Progreso', 'is_visible' => '1'])
            ->assertSessionHasErrors(['consent' => 'Confirma que el donante autorizó publicar su nombre.']);

        $this->actingAs($this->admin)
            ->post(route('admin.donors.store'), ['name' => 'Ferretería El Progreso', 'is_visible' => '1', 'consent' => '1', 'website' => 'no-es-url'])
            ->assertSessionHasErrors(['website']);

        $this->actingAs($this->admin)
            ->post(route('admin.donors.store'), ['name' => 'Ferretería El Progreso', 'is_visible' => '1', 'consent' => '1'])
            ->assertSessionHasNoErrors();

        $donor = Donor::sole();
        $this->assertNotNull($donor->consent_at);

        // Al editar ya no se vuelve a pedir
        $this->actingAs($this->admin)
            ->put(route('admin.donors.update', $donor), ['name' => 'Ferretería El Progreso S. A. S.', 'is_visible' => '1'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->get(route('admin.donors.edit', $donor))->assertSee('Autorización registrada el');
    }

    public function test_testimonial_strips_quotes_and_needs_consent(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.testimonials.store'), ['quote' => 'Hola', 'author' => 'Ana'])
            ->assertSessionHasErrors(['consent']);

        $this->actingAs($this->admin)->post(route('admin.testimonials.store'), [
            'quote' => '“Pintamos tres aulas.”',
            'author' => 'Ana',
            'role' => 'acudiente',
            'is_visible' => '1',
            'consent' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Pintamos tres aulas.', Testimonial::sole()->quote);
    }

    public function test_follows_up_volunteer_applications(): void
    {
        $new = VolunteerApplication::factory()->create(['name' => 'Luisa Nueva']);
        $old = VolunteerApplication::factory()->create(['name' => 'Carlos Archivado']);
        $old->forceFill(['status' => VolunteerStatus::Archived])->save();

        $this->actingAs($this->admin)->get(route('admin.volunteers.index', ['estado' => 'new']))
            ->assertOk()
            ->assertSee('Luisa Nueva')
            ->assertDontSee('Carlos Archivado')
            ->assertSee('Nuevas (1)');

        $this->actingAs($this->admin)->get(route('admin.volunteers.show', $new))
            ->assertOk()
            ->assertSee($new->formattedPhone())
            ->assertSee('Autorización de datos');

        $this->actingAs($this->admin)->put(route('admin.volunteers.update', $new), [
            'status' => 'contacted',
            'admin_note' => 'Ayudará en la jornada de pintura.',
        ])->assertRedirect(route('admin.volunteers.show', $new));

        $this->assertSame(VolunteerStatus::Contacted, $new->fresh()->status);
        $this->assertSame('Ayudará en la jornada de pintura.', $new->fresh()->admin_note);
    }

    public function test_admin_pages_render(): void
    {
        $account = DonationAccount::factory()->create();
        $donor = Donor::factory()->create();
        $testimonial = Testimonial::factory()->create();

        $this->actingAs($this->admin)->get('/admin/apoyanos')->assertRedirect('/admin/apoyanos/voluntarios');

        foreach ([
            route('admin.volunteers.index'),
            route('admin.accounts.index'), route('admin.accounts.create'), route('admin.accounts.edit', $account),
            route('admin.donors.index'), route('admin.donors.create'), route('admin.donors.edit', $donor),
            route('admin.testimonials.index'), route('admin.testimonials.create'), route('admin.testimonials.edit', $testimonial),
        ] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }
}
