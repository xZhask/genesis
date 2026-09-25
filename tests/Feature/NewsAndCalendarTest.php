<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Post;
use App\Models\User;
use App\Support\CalendarExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NewsAndCalendarTest extends TestCase
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

    public function test_news_shows_only_published_posts(): void
    {
        $published = Post::factory()->create(['title' => 'Publicada']);
        Post::factory()->draft()->create(['title' => 'Borrador secreto']);
        Post::factory()->scheduled()->create(['title' => 'Programada futura']);

        $this->get(route('news'))
            ->assertOk()
            ->assertSee('Publicada')
            ->assertDontSee('Borrador secreto')
            ->assertDontSee('Programada futura');

        $this->get(route('news.show', $published))->assertOk()->assertSee('Comparte esta noticia');
    }

    public function test_drafts_are_404_for_public_and_previewable_by_admin(): void
    {
        $draft = Post::factory()->draft()->create();

        $this->get(route('news.show', $draft))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('news.show', $draft))->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('news.show', $draft))
            ->assertOk()
            ->assertSee('Vista previa');
    }

    public function test_markdown_is_rendered_and_html_is_stripped(): void
    {
        $post = Post::factory()->create(['body' => "Texto **importante**.\n\n<script>alert(1)</script>\n\n[enlace](javascript:alert(1))"]);

        $this->get(route('news.show', $post))
            ->assertSee('<strong>importante</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('href="javascript:', false);
    }

    public function test_share_on_whatsapp_link(): void
    {
        $post = Post::factory()->create(['title' => 'Feria']);

        $this->get(route('news.show', $post))
            ->assertSee('https://wa.me/?text='.rawurlencode('Feria '.$post->url()), false);
    }

    public function test_calendar_list_groups_upcoming_events_by_month(): void
    {
        Event::factory()->create(['title' => 'Boletines', 'starts_at' => '2026-10-02 07:00']);
        Event::factory()->create(['title' => 'Clausura', 'starts_at' => '2026-11-27 09:00']);
        Event::factory()->create(['title' => 'Evento pasado', 'starts_at' => '2026-09-10 07:00']);
        // Evento de varios días que empezó ayer y sigue en curso
        Event::factory()->create(['title' => 'Semana cultural', 'starts_at' => '2026-09-24', 'ends_at' => '2026-09-28', 'all_day' => true]);

        $this->get(route('calendar'))
            ->assertOk()
            ->assertSeeInOrder(['Septiembre de 2026', 'Semana cultural', 'Octubre de 2026', 'Boletines', 'Noviembre de 2026', 'Clausura'])
            ->assertDontSee('Evento pasado');
    }

    public function test_level_filter_includes_whole_school_events(): void
    {
        Event::factory()->create(['title' => 'Para todos', 'starts_at' => '2026-10-02 07:00']);
        Event::factory()->create(['title' => 'Solo preescolar', 'starts_at' => '2026-10-03 07:00', 'level' => 'preschool']);
        Event::factory()->create(['title' => 'Solo secundaria', 'starts_at' => '2026-10-04 07:00', 'level' => 'secondary']);

        $this->get(route('calendar', ['nivel' => 'preescolar']))
            ->assertSee('Para todos')
            ->assertSee('Solo preescolar')
            ->assertDontSee('Solo secundaria');
    }

    public function test_month_view_shows_the_requested_month(): void
    {
        Event::factory()->create(['title' => 'Receso', 'starts_at' => '2026-10-05', 'ends_at' => '2026-10-09', 'all_day' => true]);
        Event::factory()->create(['title' => 'En noviembre', 'starts_at' => '2026-11-10 07:00']);

        $this->get(route('calendar', ['vista' => 'mes', 'mes' => '2026-10']))
            ->assertOk()
            ->assertSee('Octubre de 2026')
            ->assertSee('Receso')
            ->assertSee('Del 5 al 9 de octubre')
            ->assertDontSee('En noviembre');

        // Un mes inválido vuelve al actual
        $this->get(route('calendar', ['vista' => 'mes', 'mes' => '2026-13']))->assertOk()->assertSee('Septiembre de 2026');
    }

    public function test_ics_download_for_a_timed_event(): void
    {
        $event = Event::factory()->create([
            'title' => 'Entrega de boletines, tercer periodo',
            'starts_at' => '2026-10-02 07:00',
            'ends_at' => '2026-10-02 10:00',
            'location' => 'Aulas; bloque A',
        ]);

        $response = $this->get(route('calendar.ics', $event))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8');

        $ics = $response->getContent();
        $this->assertStringContainsString("BEGIN:VCALENDAR\r\n", $ics);
        // 7:00 a. m. en Bogotá (UTC-5) = 12:00 UTC
        $this->assertStringContainsString("DTSTART:20261002T120000Z\r\n", $ics);
        $this->assertStringContainsString("DTEND:20261002T150000Z\r\n", $ics);
        $this->assertStringContainsString('SUMMARY:Entrega de boletines\, tercer periodo', $ics);
        $this->assertStringContainsString('LOCATION:Aulas\; bloque A', $ics);

        foreach (explode("\r\n", $ics) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line), "Línea demasiado larga: {$line}");
        }
    }

    public function test_ics_for_all_day_multi_day_event_uses_exclusive_end_date(): void
    {
        $event = Event::factory()->create(['starts_at' => '2026-10-05', 'ends_at' => '2026-10-09', 'all_day' => true]);

        $ics = CalendarExport::ics($event);
        $this->assertStringContainsString("DTSTART;VALUE=DATE:20261005\r\n", $ics);
        $this->assertStringContainsString("DTEND;VALUE=DATE:20261010\r\n", $ics);

        $this->assertStringContainsString('dates=20261005%2F20261010', CalendarExport::googleUrl($event));
    }

    public function test_home_uses_real_news_and_events(): void
    {
        Post::factory()->create(['title' => 'Noticia real', 'published_at' => '2026-09-18 09:00']);
        Event::factory()->create(['title' => 'Evento real', 'starts_at' => '2026-10-02 07:00', 'location' => 'Sede principal']);

        $this->get(route('home'))
            ->assertSee('Noticia real')
            ->assertSee('18 de septiembre de 2026')
            ->assertSee('Evento real')
            ->assertSee('7:00 a. m. · Sede principal')
            ->assertSee(route('calendar.ics', Event::sole()), false);
    }
}
