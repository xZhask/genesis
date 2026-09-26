<?php

namespace Tests\Feature\Portal;

use App\Enums\ContactField;
use App\Enums\FamilyNotice;
use App\Enums\PublicationStatus;
use App\Enums\ResourceType;
use App\Enums\ResourceVisibility;
use App\Enums\Role;
use App\Mail\Family\ContactRequestReviewed;
use App\Mail\Family\EmailChangedNotice;
use App\Mail\Family\EventReminder;
use App\Mail\Family\NewCircular;
use App\Mail\Family\ReportCardsAvailable;
use App\Models\ContactUpdateRequest;
use App\Models\Event;
use App\Models\GradeItem;
use App\Models\Guardian;
use App\Models\Resource;
use App\Models\Score;
use App\Models\SentNotification;
use App\Models\User;
use App\Support\FamilyMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Correos a las familias (fase 3): cada aviso sale una sola vez, a quien
 * corresponde, respetando el tope diario y a quien no quiere recibirlos.
 * Nunca llevan notas.
 */
class FamilyMailTest extends PortalTestCase
{
    private User $admin;

    private User $andresFamily;

    private User $luciaFamily;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->admin = User::factory()->admin()->create();
        $this->andresFamily = $this->guardianOf($this->andres); // 7.°: secundaria
        $this->luciaFamily = $this->guardianOf($this->lucia);   // 3.°: primaria
        foreach ([$this->andresFamily, $this->luciaFamily] as $user) {
            $user->guardian->update(['email' => "familia{$user->id}@example.com"]);
        }
    }

    private function event(array $attributes = []): Event
    {
        return Event::create(['title' => 'Día de la ciencia', 'starts_at' => today()->addDays(2)->setTime(8, 0), 'send_reminder' => true, ...$attributes]);
    }

    public function test_event_reminders_go_to_the_event_level_once(): void
    {
        $this->event(['level' => 'secondary']);
        $this->event(['title' => 'Sin recordatorio', 'send_reminder' => false]);
        $this->event(['title' => 'Muy lejano', 'starts_at' => today()->addDays(10)]);

        $this->artisan('app:queue-event-reminders')->assertSuccessful();
        $this->artisan('app:queue-event-reminders')->assertSuccessful(); // el cron se repite
        $this->artisan('app:deliver-family-mail')->assertSuccessful();

        Mail::assertSent(EventReminder::class, 1);
        Mail::assertSent(EventReminder::class, fn ($mail) => $mail->hasTo($this->andresFamily->guardian->email)
            && $mail->event->title === 'Día de la ciencia');
        $this->assertSame(1, SentNotification::whereNotNull('sent_at')->count());
    }

    public function test_school_wide_events_reach_every_family_and_render_with_calendar_links(): void
    {
        $event = $this->event(['location' => 'Patio']);
        FamilyMail::queueEventReminder($event);
        FamilyMail::deliver();

        Mail::assertSent(EventReminder::class, 2);
        $html = (new EventReminder($this->andresFamily->guardian, $event))->render();
        $this->assertStringContainsString('calendar.google.com', $html);
        $this->assertStringContainsString(route('calendar.ics', $event), $html);
        $this->assertStringContainsString('Dejar de recibir estos avisos', $html);
    }

    public function test_closing_a_period_notifies_families_once_and_without_grades(): void
    {
        $item = GradeItem::create(['section_id' => $this->seventh->id, 'subject_id' => $this->math->id, 'period_id' => $this->year->periods[0]->id, 'component' => 'knowing', 'name' => 'Quiz']);
        Score::create(['grade_item_id' => $item->id, 'enrollment_id' => $this->enrollmentId($this->andres), 'value' => 2.3]);
        $period = $this->year->periods[0];

        $this->actingAs($this->admin)->post(route('admin.academic.periods.close', $period), ['notify' => '1'])
            ->assertSessionHas('status_message', fn ($m) => str_contains($m, 'Se avisará por correo a 2 familias'));
        $this->actingAs($this->admin)->post(route('admin.academic.periods.reopen', $period));
        $this->actingAs($this->admin)->post(route('admin.academic.periods.close', $period), ['notify' => '1']);

        FamilyMail::deliver();
        Mail::assertSent(ReportCardsAvailable::class, 2);

        $html = (new ReportCardsAvailable($this->andresFamily->guardian, $period->fresh()))->render();
        $this->assertStringContainsString('Andrés (7.° 1)', $html);
        $this->assertStringNotContainsString('2,3', $html);
        $this->assertStringNotContainsString('2.3', $html);
    }

    public function test_closing_without_the_checkbox_sends_nothing(): void
    {
        $this->actingAs($this->admin)->post(route('admin.academic.periods.close', $this->year->periods[0]));

        $this->assertSame(0, SentNotification::count());
    }

    public function test_circular_notice_reaches_only_its_grades(): void
    {
        $this->actingAs($this->admin)->post(route('admin.resources.store'), [
            'type' => 'circular', 'title' => 'Salida de 3.°', 'body' => 'Texto',
            'published_on' => today()->toDateString(), 'status' => 'published',
            'visibility' => 'families', 'audience' => ['3.°'], 'notify' => '1',
        ])->assertSessionHasNoErrors();
        FamilyMail::deliver();

        Mail::assertSent(NewCircular::class, 1);
        Mail::assertSent(NewCircular::class, fn ($mail) => $mail->hasTo($this->luciaFamily->guardian->email));

        // Editarla no reenvía; un borrador no avisa
        $circular = Resource::sole();
        $this->actingAs($this->admin)->put(route('admin.resources.update', $circular), [
            'type' => 'circular', 'title' => 'Salida de 3.° (cambio de hora)', 'body' => 'Texto',
            'published_on' => today()->toDateString(), 'status' => 'published', 'visibility' => 'families', 'audience' => ['3.°'], 'notify' => '1',
        ]);
        $draft = Resource::create(['type' => ResourceType::Circular, 'title' => 'B', 'body' => 'x', 'published_on' => today(), 'status' => PublicationStatus::Draft, 'visibility' => ResourceVisibility::Families]);
        $this->assertSame(0, FamilyMail::queueCircular($draft));
        FamilyMail::deliver();
        Mail::assertSent(NewCircular::class, 1);
    }

    public function test_contact_review_and_security_notice_to_the_previous_email(): void
    {
        $guardian = $this->andresFamily->guardian;
        $guardian->forceFill(['email_notifications' => false])->save(); // estos dos avisos se envían igual
        $request = ContactUpdateRequest::submit($guardian, ContactField::Email, 'nuevo@example.com');

        $this->actingAs($this->admin)->post(route('admin.people.contact-requests.approve', $request))->assertRedirect();
        FamilyMail::deliver();

        Mail::assertSent(EmailChangedNotice::class, fn ($mail) => $mail->hasTo("familia{$this->andresFamily->id}@example.com"));
        Mail::assertSent(ContactRequestReviewed::class, fn ($mail) => $mail->hasTo('nuevo@example.com'));
        $this->assertSame('n••••@example.com', EmailChangedNotice::mask('nuevo@example.com'));

        $other = ContactUpdateRequest::submit($this->luciaFamily->guardian, ContactField::Phone, '300 111 2233');
        $this->actingAs($this->admin)->post(route('admin.people.contact-requests.reject', $other), ['note' => 'Llámanos.']);
        FamilyMail::deliver();
        Mail::assertSent(ContactRequestReviewed::class, fn ($mail) => $mail->hasTo($this->luciaFamily->guardian->email));
    }

    public function test_daily_limit_holds_the_rest_for_tomorrow(): void
    {
        config(['school.family_mail.daily_limit' => 1]);
        FamilyMail::queueEventReminder($this->event());

        $this->assertSame(1, FamilyMail::deliver());
        $this->assertSame(0, FamilyMail::deliver());
        $this->assertSame(1, SentNotification::whereNull('sent_at')->count());

        $this->travel(1)->days();
        $this->assertSame(1, FamilyMail::deliver());
    }

    public function test_only_active_accounts_with_email_that_did_not_opt_out(): void
    {
        $this->luciaFamily->guardian->forceFill(['email_notifications' => false])->save();
        $this->andresFamily->forceFill(['is_active' => false])->save();
        $noEmail = $this->guardianOf($this->sara);
        $noEmail->guardian->update(['email' => null]);
        $noEmail->forceFill(['email' => null])->save();

        $this->assertSame(0, FamilyMail::queueEventReminder($this->event()));
    }

    public function test_unsubscribe_link_is_signed_and_asks_to_confirm(): void
    {
        $guardian = $this->andresFamily->guardian;
        $url = URL::signedRoute('family-mail.unsubscribe', $guardian);

        $this->get($url)->assertOk()->assertSee('¿Dejar de recibir avisos?');
        $this->assertTrue($guardian->fresh()->email_notifications, 'Abrir el enlace no desactiva nada');

        $this->get(str_replace($guardian->id.'/', $this->luciaFamily->guardian->id.'/', $url))->assertForbidden();
        $this->post($url)->assertOk()->assertSee('Listo');
        $this->assertFalse($guardian->fresh()->email_notifications);
    }

    public function test_guardian_changes_the_preference_in_the_portal(): void
    {
        $this->actingAs($this->andresFamily)->get(route('portal.guardian.contact'))->assertSee('Avisos por correo');
        $this->actingAs($this->andresFamily)->put(route('portal.guardian.contact.notifications'), ['email_notifications' => '0'])
            ->assertRedirect(route('portal.guardian.contact'));
        $this->assertFalse($this->andresFamily->guardian->fresh()->email_notifications);

        $this->actingAs(User::factory()->role(Role::Student)->create())
            ->put(route('portal.guardian.contact.notifications'), ['email_notifications' => '0'])->assertForbidden();
    }

    public function test_deleted_subjects_are_dropped_from_the_outbox(): void
    {
        $event = $this->event();
        FamilyMail::queueEventReminder($event);
        $event->delete();

        $this->assertSame(0, FamilyMail::deliver());
        $this->assertSame(0, SentNotification::count());
        Mail::assertNothingSent();
    }

    public function test_admin_marks_events_for_reminder(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.store'), [
            'title' => 'Izada de bandera', 'date' => today()->addDays(5)->toDateString(), 'all_day' => '1', 'send_reminder' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Event::sole()->send_reminder);
        $this->assertSame(FamilyNotice::EventReminder, FamilyNotice::from('event_reminder'));
        $this->assertInstanceOf(Guardian::class, $this->andresFamily->guardian);
    }
}
