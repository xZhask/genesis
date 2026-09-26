<?php

namespace Tests\Feature\Portal;

use App\Enums\PublicationStatus;
use App\Enums\ResourceType;
use App\Enums\ResourceVisibility;
use App\Enums\Role;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Circulares «solo familias»: no salen en la web pública, se ven en el
 * portal del acudiente (opcionalmente solo en algunos grados) y su PDF no
 * está en el disco público.
 */
class CircularVisibilityTest extends PortalTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        $this->admin = User::factory()->admin()->create();
    }

    private function circular(array $attributes = []): Resource
    {
        return Resource::create([
            'type' => ResourceType::Circular, 'title' => 'Circular', 'body' => 'Texto',
            'published_on' => today(), 'status' => PublicationStatus::Published,
            'visibility' => ResourceVisibility::Families, ...$attributes,
        ]);
    }

    public function test_admin_publishes_a_circular_only_for_some_grades(): void
    {
        $this->actingAs($this->admin)->get(route('admin.resources.create', ['tipo' => 'circulares']))
            ->assertOk()->assertSee('¿Quién la ve?')
            ->assertSee('value="families" checked', false);

        $this->actingAs($this->admin)->post(route('admin.resources.store'), [
            'type' => 'circular', 'title' => 'Salida pedagógica', 'body' => '',
            'file' => UploadedFile::fake()->create('salida.pdf', 100, 'application/pdf'),
            'published_on' => today()->toDateString(), 'status' => 'published',
            'visibility' => 'families', 'audience' => ['7.°'],
        ])->assertSessionHasNoErrors()
            ->assertSessionHas('status_message', 'Se guardó «Salida pedagógica». Ya la ven en el portal: Familias de 7.°.');

        $resource = Resource::sole();
        $this->assertSame(['7.°'], $resource->audience);
        Storage::disk('local')->assertExists($resource->file_path);
        Storage::disk('public')->assertMissing($resource->file_path);
        $this->assertSame(route('circulars.file', $resource), $resource->fileUrl());

        $this->get(route('resources'))->assertDontSee('Salida pedagógica');
        $this->get(route('resources.circulars'))->assertDontSee('Salida pedagógica');
    }

    public function test_each_family_sees_only_the_circulars_addressed_to_it(): void
    {
        $this->circular(['title' => 'Salida de 7.°', 'audience' => ['7.°']]);
        $this->circular(['title' => 'Reunión general']);
        $this->circular(['title' => 'Vacaciones', 'visibility' => ResourceVisibility::Public]);
        $this->circular(['title' => 'Borrador interno', 'status' => PublicationStatus::Draft]);

        $andres = $this->guardianOf($this->andres);
        $lucia = $this->guardianOf($this->lucia);

        $this->actingAs($andres)->get(route('portal.guardian.circulars'))
            ->assertOk()->assertSee('Salida de 7.°')->assertSee('Familias de 7.°')->assertSee('Reunión general')->assertSee('Vacaciones')
            ->assertDontSee('Borrador interno');

        $this->actingAs($lucia)->get(route('portal.guardian.circulars'))
            ->assertOk()->assertDontSee('Salida de 7.°')->assertSee('Reunión general');

        // Aviso de circulares nuevas en el menú
        $this->actingAs($lucia)->get(route('portal.guardian.circulars'))->assertSee('nav-count', false);

        $this->get(route('resources'))->assertSee('Vacaciones')->assertDontSee('Reunión general');
    }

    public function test_the_pdf_is_served_only_to_whoever_can_see_the_circular(): void
    {
        Storage::disk('local')->put('resources/salida.pdf', '%PDF-1.4 prueba');
        $circular = $this->circular(['audience' => ['7.°']]);
        $circular->forceFill(['file_path' => 'resources/salida.pdf', 'file_name' => 'salida.pdf', 'file_size' => 15])->save();
        $url = route('circulars.file', $circular);

        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($this->guardianOf($this->andres))->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->guardianOf($this->lucia))->get($url)->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Student)->create())->get($url)->assertForbidden();
        $this->actingAs($this->teacher)->get($url)->assertForbidden();
        $this->actingAs($this->admin)->get($url)->assertOk();

        $circular->update(['status' => PublicationStatus::Draft]);
        $this->actingAs($this->guardianOf($this->andres))->get($url)->assertForbidden();
    }

    public function test_changing_visibility_moves_the_pdf_between_disks(): void
    {
        $this->actingAs($this->admin)->post(route('admin.resources.store'), [
            'type' => 'circular', 'title' => 'Calendario', 'body' => '',
            'file' => UploadedFile::fake()->create('calendario.pdf', 50, 'application/pdf'),
            'published_on' => today()->toDateString(), 'status' => 'published', 'visibility' => 'public',
        ])->assertSessionHasNoErrors();
        $resource = Resource::sole();
        Storage::disk('public')->assertExists($resource->file_path);

        $this->actingAs($this->admin)->put(route('admin.resources.update', $resource), [
            'type' => 'circular', 'title' => 'Calendario', 'body' => '',
            'published_on' => today()->toDateString(), 'status' => 'published', 'visibility' => 'families',
        ])->assertSessionHasNoErrors();

        Storage::disk('local')->assertExists($resource->file_path);
        Storage::disk('public')->assertMissing($resource->file_path);

        $resource->fresh()->delete();
        Storage::disk('local')->assertMissing($resource->file_path);
    }

    public function test_other_resources_are_always_public_and_grades_are_validated(): void
    {
        $this->actingAs($this->admin)->post(route('admin.resources.store'), [
            'type' => 'schedule', 'title' => 'Jornada', 'body' => 'De 7:00 a 12:00',
            'published_on' => today()->toDateString(), 'status' => 'published',
            'visibility' => 'families', 'audience' => ['7.°'],
        ])->assertSessionHasNoErrors();
        $this->assertSame(ResourceVisibility::Public, Resource::sole()->visibility);
        $this->assertNull(Resource::sole()->audience);

        $this->actingAs($this->admin)->post(route('admin.resources.store'), [
            'type' => 'circular', 'title' => 'X', 'body' => 'Texto',
            'published_on' => today()->toDateString(), 'status' => 'published',
            'visibility' => 'families', 'audience' => ['11.°'],
        ])->assertSessionHasErrors('audience.0');
    }

    public function test_only_guardians_have_the_circulars_page(): void
    {
        $this->actingAs(User::factory()->role(Role::Student)->create())->get(route('portal.guardian.circulars'))->assertForbidden();
        $this->actingAs($this->teacher)->get(route('portal.guardian.circulars'))->assertForbidden();
        $this->actingAs($this->guardianOf($this->andres))->get(route('portal.guardian.circulars'))
            ->assertOk()->assertSee('Todavía no hay circulares');
    }
}
