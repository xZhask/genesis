<?php

namespace Tests\Feature;

use Tests\TestCase;

class AboutPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_shows_official_statements_literally(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Somos una institución educativa cristiana de carácter privado')
            ->assertSee('Formar niños y niñas con valores y principios cristianos, autónomos, íntegros e innovadores')
            ->assertSee('Para el año 2030 ser una institución líder en educación preescolar, primaria y secundaria')
            ->assertSeeInOrder(['Amor', 'Respeto', 'Responsabilidad', 'Solidaridad', 'Creatividad', 'Gratitud', 'Integridad', 'Servicio', 'Liderazgo'])
            ->assertSeeInOrder(['Educación integral', 'Fe y valores cristianos', 'Creatividad y liderazgo', 'Convivencia y servicio'])
            ->assertSee('Formación académica con enfoque constructivista.')
            ->assertSee('Fomentar el aprendizaje del inglés como segunda lengua.');
    }

    public function test_includes_every_level_with_its_grades_and_anchor(): void
    {
        $this->get(route('about'))
            ->assertSee('id="niveles"', false)
            ->assertSee('id="preescolar"', false)
            ->assertSee('id="primaria"', false)
            ->assertSee('id="secundaria"', false)
            ->assertSeeInOrder(['Párvulos', 'Prejardín', 'Jardín', 'Transición', '1.°', '5.°', '6.°', '9.°']);
    }

    public function test_never_mentions_upper_secondary(): void
    {
        $html = $this->get(route('about'))->getContent();

        foreach (['bachillerato', 'Saber 11', 'grado 11', '10.°', '11.°'] as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase($forbidden, $html);
        }
    }

    public function test_old_levels_url_redirects_to_about(): void
    {
        $this->get('/niveles')->assertStatus(301)->assertRedirect('/nosotros#niveles');
    }

    public function test_home_level_links_point_to_about_sections(): void
    {
        $this->get(route('home'))
            ->assertSee('href="'.route('about').'#preescolar"', false)
            ->assertSee('href="'.route('about').'#secundaria"', false);
    }

    public function test_menu_has_six_items_and_calendar_marks_news(): void
    {
        $html = $this->get(route('calendar'))->getContent();

        preg_match('#<ul class="menu" id="menu">(.*?)</ul>#s', $html, $menu);
        $this->assertSame(7, substr_count($menu[1], '<li'), '6 opciones + "Ingresar al portal" del menú móvil');
        $this->assertStringNotContainsString('>Niveles<', $menu[1]);
        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('news'), '#').'"\s+aria-current="page"#', $menu[1]);
    }
}
