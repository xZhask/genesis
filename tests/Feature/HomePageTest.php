<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_home_shows_hero_levels_and_calls_to_action(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Aquí se aprende con alegría y se crece con propósito')
            ->assertSee('href="'.route('admissions').'#solicitud"', false)
            ->assertSee('Ingresar al portal')
            ->assertSeeInOrder(['Preescolar', 'Básica primaria', 'Básica secundaria'])
            ->assertSeeInOrder(['Párvulos', 'Prejardín', 'Jardín', 'Transición'])
            ->assertSee('Comprometidos con Dios y la sociedad');
    }

    public function test_without_real_content_dynamic_sections_are_hidden(): void
    {
        $this->get(route('home'))
            ->assertDontSee('id="noticias-title"', false)
            ->assertDontSee('Momentos Génesis')
            ->assertDontSee('data-copy', false)
            ->assertDontSee('/demo/', false)
            ->assertDontSee('Contenido de ejemplo');
    }

    public function test_without_bank_data_donation_card_invites_to_write(): void
    {
        $this->get(route('home'))
            ->assertSee('Escríbenos y te contamos cómo puedes aportar al colegio.')
            ->assertSee('mailto:cecgenesis16@gmail.com?subject=', false);
    }

    public function test_hero_badge_only_when_configured(): void
    {
        $this->get(route('home'))->assertDontSee('class="chip"', false);

        config(['school.admissions_badge' => 'Matrículas 2027 abiertas']);

        $this->get(route('home'))->assertSee('Matrículas 2027 abiertas');
    }

    public function test_demo_content_fills_every_section(): void
    {
        config(['school.demo_content' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Contenido de ejemplo para revisar el diseño')
            ->assertSee('Momentos Génesis')
            ->assertSee('demo/nivel-preescolar.webp', false)
            ->assertSee('data-copy="000-000000-00"', false);
        $this->get(route('about'))->assertOk()->assertSee('demo/nivel-secundaria.webp', false);

        config(['school.demo_content' => false]);
        $this->get(route('home'))->assertDontSee('demo/nivel-', false);
    }

    public function test_demo_content_never_appears_in_production(): void
    {
        config(['school.demo_content' => true]);
        $this->app['env'] = 'production';

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Contenido de ejemplo')
            ->assertDontSee('/demo/', false);
    }
}
