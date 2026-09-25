<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicLayoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public static function publicRoutes(): array
    {
        return [
            'inicio' => ['home'],
            'nosotros' => ['about'],
            'niveles' => ['levels'],
            'admisiones' => ['admissions'],
            'noticias' => ['news'],
            'calendario' => ['calendar'],
            'galería' => ['gallery'],
            'recursos' => ['resources'],
            'apóyanos' => ['support'],
            'política de datos' => ['privacy'],
            'ingresar' => ['login'],
        ];
    }

    #[DataProvider('publicRoutes')]
    public function test_every_menu_link_responds(string $route): void
    {
        $this->get(route($route))->assertOk();
    }

    public function test_layout_shows_official_contact_data(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Carrera 18 #16-22, Barrio Buenos Aires, Zambrano, Bolívar')
            ->assertSee('+57 321 797 5579')
            ->assertSee('tel:+573217975579', false)
            ->assertSee('cecgenesis16@gmail.com')
            ->assertSee('https://www.facebook.com/cecgenesis', false)
            ->assertSee('Centro Educativo Cristiano Génesis');
    }

    public function test_layout_does_not_publish_example_or_unconfirmed_data(): void
    {
        $html = $this->get(route('home'))->getContent();

        foreach (['bachillerato', 'Saber 11', 'grado 11', '1:18', 'años formando', '300 000 0000', 'colegiogenesis.edu.co', 'Calle 00', 'PSE'] as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase($forbidden, $html, "La página no debe contener «{$forbidden}».");
        }
    }

    public function test_pending_items_stay_hidden_until_confirmed(): void
    {
        $this->get(route('home'))
            ->assertDontSee('wa.me', false)
            ->assertDontSee('Horarios</h2>', false);
    }

    public function test_pending_items_appear_once_configured(): void
    {
        config([
            'school.whatsapp.enabled' => true,
            'school.office_hours' => ['Secretaría: lunes a viernes, 7:00 a. m. – 3:00 p. m.'],
        ]);

        $this->get(route('home'))
            ->assertSee('https://wa.me/573217975579', false)
            ->assertSee('Secretaría: lunes a viernes, 7:00 a. m. – 3:00 p. m.');
    }

    public function test_current_page_is_marked_in_menu(): void
    {
        $html = $this->get(route('about'))->getContent();

        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('about'), '#').'"\s+aria-current="page"#', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
    }

    public function test_theme_toggle_is_accessible(): void
    {
        $this->get(route('home'))
            ->assertSee('data-theme-toggle aria-label="Modo oscuro" aria-pressed="false"', false)
            ->assertSee("localStorage.getItem('genesis-theme')", false);
    }
}
