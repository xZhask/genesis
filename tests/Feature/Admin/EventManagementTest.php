<?php

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_creates_a_timed_event(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.store'), [
            'title' => 'Entrega de boletines', 'visibility' => 'public',
            'date' => '2026-10-02',
            'start_time' => '07:00',
            'end_time' => '10:00',
            'location' => 'Aulas',
            'level' => '',
        ])->assertRedirect(route('admin.events.index'));

        $event = Event::sole();
        $this->assertSame('2026-10-02 07:00', $event->starts_at->format('Y-m-d H:i'));
        $this->assertSame('2026-10-02 10:00', $event->ends_at->format('Y-m-d H:i'));
        $this->assertFalse($event->all_day);
        $this->assertNull($event->level);
        $this->assertSame('7:00 a. m. – 10:00 a. m. · Aulas', $event->metaLabel());
    }

    public function test_creates_an_all_day_multi_day_event_for_one_level(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.store'), [
            'title' => 'Receso', 'visibility' => 'public',
            'date' => '2026-10-05',
            'end_date' => '2026-10-09',
            'all_day' => '1',
            'level' => 'primary',
        ])->assertSessionHasNoErrors();

        $event = Event::sole();
        $this->assertTrue($event->all_day);
        $this->assertTrue($event->isMultiDay());
        $this->assertSame('primary', $event->level);
        $this->assertSame('Del 5 al 9 de octubre', $event->whenLabel());
    }

    public function test_validation(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.store'), [
            'title' => 'Sin hora', 'visibility' => 'public',
            'date' => '2026-10-02',
        ])->assertSessionHasErrors(['start_time' => 'Indica la hora de inicio o marca "Todo el día".']);

        $this->actingAs($this->admin)->post(route('admin.events.store'), [
            'title' => 'Horas al revés', 'visibility' => 'public',
            'date' => '2026-10-02',
            'start_time' => '10:00',
            'end_time' => '08:00',
            'end_date' => '2026-10-01',
            'level' => 'media',
        ])->assertSessionHasErrors(['end_date', 'level']);

        $this->actingAs($this->admin)->post(route('admin.events.store'), [
            'title' => 'Horas al revés', 'visibility' => 'public',
            'date' => '2026-10-02',
            'start_time' => '10:00',
            'end_time' => '08:00',
        ])->assertSessionHasErrors(['end_time' => 'La hora de fin debe ser posterior a la de inicio.']);

        $this->assertSame(0, Event::count());
    }

    public function test_updates_and_deletes(): void
    {
        $event = Event::factory()->create(['title' => 'Antes']);

        $this->actingAs($this->admin)->put(route('admin.events.update', $event), [
            'title' => 'Después', 'visibility' => 'public',
            'date' => '2026-11-20',
            'all_day' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Después', $event->fresh()->title);
        $this->assertTrue($event->fresh()->all_day);

        $this->actingAs($this->admin)->delete(route('admin.events.destroy', $event));
        $this->assertModelMissing($event);
    }

    public function test_index_separates_upcoming_and_past(): void
    {
        Event::factory()->create(['title' => 'Evento que viene', 'starts_at' => now()->addDays(3)]);
        Event::factory()->create(['title' => 'Evento que ya ocurrió', 'starts_at' => now()->subDays(3)]);

        $this->actingAs($this->admin)->get(route('admin.events.index'))->assertSee('Evento que viene')->assertDontSee('Evento que ya ocurrió');
        $this->actingAs($this->admin)->get(route('admin.events.index', ['vista' => 'pasados']))->assertSee('Evento que ya ocurrió')->assertDontSee('Evento que viene');
    }
}
