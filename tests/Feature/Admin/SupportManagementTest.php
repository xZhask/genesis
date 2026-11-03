<?php

namespace Tests\Feature\Admin;

use App\Enums\VolunteerStatus;
use App\Models\DonationAccount;
use App\Models\Donor;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\VolunteerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_donor_photo_needs_its_own_consent_and_is_stored_small(): void
    {
        Storage::fake('public');
        $base = ['name' => 'Familia Pérez', 'is_visible' => '1', 'consent' => '1'];

        // La autorización del nombre no cubre la foto
        $this->actingAs($this->admin)
            ->post(route('admin.donors.store'), $base + ['photo' => UploadedFile::fake()->image('foto.jpg', 1600, 900)])
            ->assertSessionHasErrors(['photo_consent' => 'Confirma que el donante autorizó publicar esta foto.']);
        $this->assertSame(0, Donor::count());

        $this->actingAs($this->admin)
            ->post(route('admin.donors.store'), $base + ['photo' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'), 'photo_consent' => '1'])
            ->assertSessionHasErrors(['photo']);

        $this->actingAs($this->admin)
            ->post(route('admin.donors.store'), $base + ['photo' => UploadedFile::fake()->image('foto.jpg', 1600, 900), 'photo_consent' => '1'])
            ->assertSessionHasNoErrors();

        $donor = Donor::sole();
        $this->assertNotNull($donor->photo_consent_at);
        Storage::disk('public')->assertExists($donor->photo_path);

        // Cuadrada y pequeña, sin importar el tamaño original
        $this->assertSame([240, 240], array_slice(getimagesize(Storage::disk('public')->path($donor->photo_path)), 0, 2));
    }

    public function test_donor_photo_can_be_replaced_removed_and_is_deleted_with_the_donor(): void
    {
        Storage::fake('public');
        $donor = Donor::factory()->create();
        $edit = ['name' => $donor->name, 'is_visible' => '1'];

        // Editar sin subir foto no pide autorización
        $this->actingAs($this->admin)->put(route('admin.donors.update', $donor), $edit)->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->put(route('admin.donors.update', $donor), $edit + [
            'photo' => UploadedFile::fake()->image('uno.png', 300, 300), 'photo_consent' => '1',
        ])->assertSessionHasNoErrors();
        $first = $donor->refresh()->photo_path;

        // Una foto más pequeña que el tope no se agranda
        $this->actingAs($this->admin)->put(route('admin.donors.update', $donor), $edit + [
            'photo' => UploadedFile::fake()->image('dos.jpg', 100, 80), 'photo_consent' => '1',
        ])->assertSessionHasNoErrors();
        $second = $donor->refresh()->photo_path;

        Storage::disk('public')->assertMissing($first);
        $this->assertSame([80, 80], array_slice(getimagesize(Storage::disk('public')->path($second)), 0, 2));

        // Quitarla borra también la fecha de autorización
        $this->actingAs($this->admin)->put(route('admin.donors.update', $donor), $edit + ['remove_photo' => '1'])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($donor->refresh()->photo_path);
        $this->assertNull($donor->photo_consent_at);

        $this->actingAs($this->admin)->put(route('admin.donors.update', $donor), $edit + [
            'photo' => UploadedFile::fake()->image('tres.jpg', 300, 300), 'photo_consent' => '1',
        ]);
        $third = $donor->refresh()->photo_path;

        $this->actingAs($this->admin)->delete(route('admin.donors.destroy', $donor));
        Storage::disk('public')->assertMissing($third);
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
