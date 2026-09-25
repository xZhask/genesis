<?php

namespace Tests\Feature;

use App\Enums\ResourceType;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ResourcesPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-09-25 10:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_empty_page_invites_to_contact_and_hides_sections(): void
    {
        $this->get(route('resources'))
            ->assertOk()
            ->assertSee('Pronto publicaremos aquí')
            ->assertSee('tel:+573217975579', false)
            ->assertDontSee('id="circulares"', false)
            ->assertDontSee('id="utiles"', false);
    }

    public function test_shows_only_published_resources_and_only_non_empty_sections(): void
    {
        Resource::factory()->create(['title' => 'Circular publicada']);
        Resource::factory()->draft()->create(['title' => 'Circular en borrador']);
        Resource::factory()->ofType(ResourceType::Schedule)->create(['title' => 'Jornada escolar', 'body' => "| Nivel | Entrada |\n|---|---|\n| Preescolar | 7:00 a. m. |"]);

        $this->get(route('resources'))
            ->assertOk()
            ->assertSee('Circular publicada')
            ->assertDontSee('Circular en borrador')
            ->assertSee('id="circulares"', false)
            ->assertSee('id="horarios"', false)
            ->assertSee('<table>', false)
            ->assertDontSee('id="utiles"', false)
            ->assertDontSee('id="uniformes"', false)
            ->assertDontSee('Pronto publicaremos aquí');
    }

    public function test_circulars_newest_first_with_new_tag_and_archive_link(): void
    {
        Resource::factory()->create(['title' => 'Circular reciente', 'published_on' => '2026-09-22']);
        Resource::factory()->create(['title' => 'Circular vieja', 'published_on' => '2026-08-01']);

        $this->get(route('resources'))
            ->assertSeeInOrder(['Circular reciente', 'Circular vieja'])
            ->assertSeeInOrder(['22 de septiembre de 2026', 'Nueva', 'Circular reciente'])
            ->assertDontSee('Ver circulares anteriores');

        Resource::factory()->count(6)->create();

        $this->get(route('resources'))->assertSee('Ver circulares anteriores');
        $this->get(route('resources.circulars'))->assertOk()->assertSee('Circular vieja');
    }

    public function test_pdf_link_shows_size(): void
    {
        Resource::factory()->create(['file_path' => 'resources/x.pdf', 'file_name' => 'x.pdf', 'file_size' => 245760]);
        Resource::factory()->create(['file_path' => 'resources/y.pdf', 'file_name' => 'y.pdf', 'file_size' => 1258291]);

        $this->get(route('resources'))
            ->assertSee('/storage/resources/x.pdf', false)
            ->assertSee('(240 KB)')
            ->assertSee('(1,2 MB)');
    }

    public function test_supplies_show_latest_list_per_grade_and_mark_missing_grades(): void
    {
        Resource::factory()->supplies('3.°', 2026)->create(['body' => 'Lista vieja de tercero']);
        Resource::factory()->supplies('3.°', 2027)->create(['body' => 'Lista nueva de tercero']);
        Resource::factory()->supplies('Párvulos', 2027)->create();

        $this->get(route('resources'))
            ->assertSee('Lista nueva de tercero')
            ->assertDontSee('Lista vieja de tercero')
            ->assertSee('href="#utiles-3"', false)
            ->assertSee('href="#utiles-parvulos"', false)
            ->assertSee('4.°<span class="sr-only"> (aún no publicada)</span>', false)
            ->assertSeeInOrder(['Párvulos', 'Prejardín', 'Jardín', 'Transición', '1.°', '9.°']);
    }

    public function test_never_mentions_upper_secondary(): void
    {
        Resource::factory()->supplies('9.°')->create();

        $html = $this->get(route('resources'))->getContent();

        foreach (['10.°', '11.°', 'bachillerato'] as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase($forbidden, $html);
        }
    }

    public function test_home_quick_links_point_to_sections(): void
    {
        $this->get(route('home'))
            ->assertSee('href="'.route('resources').'#uniformes"', false)
            ->assertSee('href="'.route('resources').'#horarios"', false);
    }
}
