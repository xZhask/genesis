<?php

namespace Tests\Feature\Admin;

use App\Enums\PublicationStatus;
use App\Enums\ResourceType;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceManagementTest extends TestCase
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
            'type' => 'circular',
            'title' => 'Entrega de boletines',
            'summary' => 'Reunión con los directores de grupo.',
            'body' => 'Apreciadas familias…',
            'published_on' => '2026-09-20',
            'status' => 'published',
            'visibility' => 'public',
            ...$overrides,
        ];
    }

    private function pdf(string $name = 'circular.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 200, 'application/pdf');
    }

    public function test_publishes_a_circular_with_pdf(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.resources.store'), $this->data(['body' => '', 'file' => $this->pdf()]))
            ->assertRedirect(route('admin.resources.index', ['tipo' => 'circulares']))
            ->assertSessionHas('status_message', 'Se guardó «Entrega de boletines». Ya se ve en la web.');

        $resource = Resource::sole();
        $this->assertSame(ResourceType::Circular, $resource->type);
        $this->assertSame('circular.pdf', $resource->file_name);
        $this->assertSame(200 * 1024, $resource->file_size);
        Storage::disk('public')->assertExists($resource->file_path);
        $this->assertStringEndsWith('.pdf', $resource->file_path);
    }

    public function test_circular_needs_a_pdf_or_text(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.resources.store'), $this->data(['body' => '']))
            ->assertSessionHasErrors(['body' => 'Adjunta un PDF o escribe el texto.']);

        $this->assertSame(0, Resource::count());
    }

    public function test_rejects_a_file_that_is_not_really_a_pdf(): void
    {
        // Archivo real (no "fake": esos informan el tipo según el nombre) con texto dentro
        $path = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($path, 'Esto es texto, no un PDF.');
        $fake = new UploadedFile($path, 'circular.pdf', 'application/pdf', null, true);

        $this->actingAs($this->admin)
            ->post(route('admin.resources.store'), $this->data(['file' => $fake]))
            ->assertSessionHasErrors(['file' => 'El archivo debe ser un PDF.']);

        $this->actingAs($this->admin)
            ->post(route('admin.resources.store'), $this->data(['file' => UploadedFile::fake()->create('grande.pdf', 6000, 'application/pdf')]))
            ->assertSessionHasErrors(['file' => 'El PDF pesa más de 5 MB. Usa uno más liviano.']);

        $this->assertSame(0, Resource::count());
    }

    public function test_supplies_list_requires_a_real_grade_and_gets_a_title(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.resources.store'), $this->data(['type' => 'supplies', 'title' => '', 'grade' => '11.°', 'school_year' => '2027']))
            ->assertSessionHasErrors(['grade' => 'Elige un grado del colegio.']);

        $this->actingAs($this->admin)
            ->post(route('admin.resources.store'), $this->data(['type' => 'supplies', 'title' => '', 'grade' => '3.°', 'school_year' => '']))
            ->assertSessionHasErrors(['school_year']);

        $this->actingAs($this->admin)
            ->post(route('admin.resources.store'), $this->data(['type' => 'supplies', 'title' => '', 'grade' => '3.°', 'school_year' => '2027']))
            ->assertSessionHasNoErrors();

        $this->assertSame(['Lista de útiles 3.° 2027', '3.°'], [Resource::sole()->title, Resource::sole()->grade]);
    }

    public function test_fields_that_do_not_apply_to_the_type_are_discarded(): void
    {
        $this->actingAs($this->admin)->post(route('admin.resources.store'), $this->data([
            'grade' => '3.°',
            'image' => UploadedFile::fake()->image('foto.jpg'),
            'image_alt' => 'Foto',
        ]))->assertSessionHasNoErrors();

        $resource = Resource::sole();
        $this->assertNull($resource->grade);
        $this->assertNull($resource->image_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_uniform_requires_text_and_photo_needs_alt_and_consent(): void
    {
        $uniform = ['type' => 'uniform', 'title' => 'Uniforme de diario'];

        $this->actingAs($this->admin)
            ->post(route('admin.resources.store'), $this->data([...$uniform, 'body' => '', 'file' => $this->pdf()]))
            ->assertSessionHasErrors(['body' => 'Escribe el texto.']);

        $this->actingAs($this->admin)
            ->post(route('admin.resources.store'), $this->data([...$uniform, 'image' => UploadedFile::fake()->image('u.jpg', 1600, 1200)]))
            ->assertSessionHasErrors(['image_alt', 'minors_consent']);

        $this->actingAs($this->admin)->post(route('admin.resources.store'), $this->data([
            ...$uniform,
            'image' => UploadedFile::fake()->image('u.jpg', 1600, 1200),
            'image_alt' => 'Jardinera azul con camisa blanca',
            'minors_consent' => '1',
        ]))->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists(Resource::sole()->image_path.'-600.webp');
    }

    public function test_replacing_removing_and_deleting_clean_up_files(): void
    {
        $this->actingAs($this->admin)->post(route('admin.resources.store'), $this->data(['file' => $this->pdf('uno.pdf')]));
        $resource = Resource::sole();
        $first = $resource->file_path;

        $this->actingAs($this->admin)->put(route('admin.resources.update', $resource), $this->data(['file' => $this->pdf('dos.pdf')]));
        Storage::disk('public')->assertMissing($first);
        $second = $resource->fresh()->file_path;
        $this->assertSame('dos.pdf', $resource->fresh()->file_name);

        $this->actingAs($this->admin)->put(route('admin.resources.update', $resource), $this->data(['remove_file' => '1']));
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($resource->fresh()->file_path);

        $this->actingAs($this->admin)->put(route('admin.resources.update', $resource), $this->data(['file' => $this->pdf('tres.pdf')]));
        $third = $resource->fresh()->file_path;

        $this->actingAs($this->admin)->delete(route('admin.resources.destroy', $resource))
            ->assertRedirect(route('admin.resources.index', ['tipo' => 'circulares']));
        $this->assertModelMissing($resource);
        Storage::disk('public')->assertMissing($third);
    }

    public function test_draft_is_saved_but_not_public(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.resources.store'), $this->data(['status' => 'draft', 'title' => 'Circular pendiente']))
            ->assertSessionHas('status_message', 'Se guardó «Circular pendiente». Quedó como borrador.');

        $this->assertSame(PublicationStatus::Draft, Resource::sole()->status);
        $this->get(route('resources'))->assertDontSee('Circular pendiente');
    }

    public function test_admin_pages_render_with_tabs(): void
    {
        Resource::factory()->create(['title' => 'Una circular']);
        Resource::factory()->supplies('Jardín')->create();
        Resource::factory()->supplies('2.°')->create();

        $this->actingAs($this->admin)->get(route('admin.resources.index'))->assertOk()->assertSee('Una circular')->assertSee('+ Nueva circular');
        $this->actingAs($this->admin)->get(route('admin.resources.index', ['tipo' => 'utiles']))
            ->assertOk()
            ->assertSeeInOrder(['Jardín', '2.°'])
            ->assertDontSee('Una circular');
        $this->actingAs($this->admin)->get(route('admin.resources.create', ['tipo' => 'uniformes']))
            ->assertOk()
            ->assertSee('value="uniform" checked', false);
        $this->actingAs($this->admin)->get(route('admin.resources.edit', Resource::first()))->assertOk()->assertSee('Eliminar');
    }
}
