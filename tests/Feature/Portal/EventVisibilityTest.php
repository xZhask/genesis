<?php

namespace Tests\Feature\Portal;

use App\Enums\ResourceVisibility;
use App\Enums\Role;
use App\Mail\Family\EventReminder;
use App\Models\Event;
use App\Models\User;
use App\Support\FamilyMail;
use Illuminate\Support\Facades\Mail;

/**
 * Eventos «solo familias»: no salen en la web pública (calendario, inicio,
 * .ics) y en el portal los ve solo quien corresponde.
 */
class EventVisibilityTest extends PortalTestCase
{
    private User $andresFamily; // 7.°, secundaria

    private User $luciaFamily;  // 3.°, primaria

    protected function setUp(): void
    {
        parent::setUp();

        $this->andresFamily = $this->guardianOf($this->andres);
        $this->luciaFamily = $this->guardianOf($this->lucia);
    }

    private function event(array $attributes = []): Event
    {
        return Event::create([
            'title' => 'Evento', 'starts_at' => today()->addDays(3)->setTime(8, 0),
            'visibility' => ResourceVisibility::Families, ...$attributes,
        ]);
    }

    public function test_admin_creates_a_family_event_for_some_grades(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.events.create'))
            ->assertOk()->assertSee('¿Quién lo ve?')->assertSee('value="families" checked', false);

        $this->actingAs($admin)->post(route('admin.events.store'), [
            'title' => 'Salida al vivero', 'date' => today()->addDays(4)->toDateString(), 'start_time' => '07:30',
            'visibility' => 'families', 'audience' => ['7.°'],
        ])->assertSessionHasNoErrors();

        $event = Event::sole();
        $this->assertTrue($event->isForFamiliesOnly());
        $this->assertSame('Familias de 7.°', $event->audienceLabel());

        $this->actingAs($admin)->get(route('admin.events.index'))->assertSee('Familias de 7.°');
    }

    public function test_family_events_never_reach_the_public_web(): void
    {
        $this->event(['title' => 'Salida privada']);
        $this->event(['title' => 'Vacaciones', 'visibility' => ResourceVisibility::Public]);

        $this->get(route('calendar'))->assertOk()->assertSee('Vacaciones')->assertDontSee('Salida privada');
        $this->get(route('calendar', ['vista' => 'mes', 'mes' => today()->addDays(3)->format('Y-m')]))
            ->assertOk()->assertDontSee('Salida privada');
        $this->get(route('home'))->assertOk()->assertDontSee('Salida privada');
        $this->get(route('news'))->assertOk()->assertDontSee('Salida privada');
    }

    public function test_each_family_sees_its_events_in_the_portal(): void
    {
        $this->event(['title' => 'Salida de 7.°', 'audience' => ['7.°']]);
        $this->event(['title' => 'Reunión de primaria', 'level' => 'primary']);
        $this->event(['title' => 'Día de la familia']);
        $this->event(['title' => 'Vacaciones', 'visibility' => ResourceVisibility::Public]);

        $this->actingAs($this->andresFamily)->get(route('portal.calendar'))->assertOk()
            ->assertSee('Salida de 7.°')->assertSee('Familias de 7.°')->assertSee('Día de la familia')->assertSee('Vacaciones')
            ->assertDontSee('Reunión de primaria');

        $this->actingAs($this->luciaFamily)->get(route('portal.calendar'))->assertOk()
            ->assertSee('Reunión de primaria')->assertSee('Día de la familia')
            ->assertDontSee('Salida de 7.°');

        // El personal del colegio ve todos
        $this->actingAs($this->teacher)->get(route('portal.calendar'))->assertOk()
            ->assertSee('Salida de 7.°')->assertSee('Reunión de primaria');

        $this->actingAs(User::factory()->role(Role::Student)->create())->get(route('portal.calendar'))->assertForbidden();
    }

    public function test_ics_of_a_family_event_needs_the_right_account(): void
    {
        $private = $this->event(['audience' => ['7.°']]);
        $public = $this->event(['visibility' => ResourceVisibility::Public]);

        $this->get(route('calendar.ics', $public))->assertOk()->assertHeader('content-type', 'text/calendar; charset=utf-8');
        $this->get(route('calendar.ics', $private))->assertRedirect(route('login'));
        $this->actingAs($this->luciaFamily)->get(route('calendar.ics', $private))->assertForbidden();
        $this->actingAs($this->andresFamily)->get(route('calendar.ics', $private))->assertOk();
    }

    public function test_reminders_follow_the_same_grades(): void
    {
        Mail::fake();
        foreach ([$this->andresFamily, $this->luciaFamily] as $user) {
            $user->guardian->update(['email' => "familia{$user->id}@example.com"]);
        }

        FamilyMail::queueEventReminder($this->event(['audience' => ['3.°'], 'send_reminder' => true]));
        FamilyMail::deliver();

        Mail::assertSent(EventReminder::class, 1);
        Mail::assertSent(EventReminder::class, fn ($mail) => $mail->hasTo($this->luciaFamily->guardian->email));
    }
}
