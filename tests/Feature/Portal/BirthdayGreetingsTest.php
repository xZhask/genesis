<?php

namespace Tests\Feature\Portal;

use App\Enums\Role;
use App\Mail\BirthdayGreetingMail;
use App\Models\BirthdayGreeting;
use App\Models\SentNotification;
use App\Models\Student;
use App\Models\User;
use App\Support\Birthdays;
use App\Support\FamilyMail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Cumpleaños: la lista del admin y la tarjeta. Sale una sola vez, a la persona o
 * a su acudiente, no dice la edad y se puede dejar de recibir.
 */
class BirthdayGreetingsTest extends PortalTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->admin = User::factory()->admin()->create();

        // La fábrica da fechas al azar: cada prueba pone solo los cumpleaños que necesita
        Student::query()->update(['birth_date' => null]);
    }

    private function bornToday(int $yearsAgo = 13): string
    {
        return today()->subYears($yearsAgo)->toDateString();
    }

    private function sendAll(): void
    {
        Artisan::call('app:queue-birthday-greetings');
        Artisan::call('app:deliver-family-mail');
    }

    public function test_teacher_with_email_receives_the_card_once(): void
    {
        $this->teacher->update(['birth_date' => $this->bornToday(40), 'email' => 'edgar@example.com']);

        $this->sendAll();
        $this->sendAll();

        Mail::assertSent(BirthdayGreetingMail::class, 1);
        Mail::assertSent(BirthdayGreetingMail::class, fn ($mail) => $mail->hasTo('edgar@example.com'));
        $this->assertNotNull(BirthdayGreeting::sole()->sent_at);
    }

    public function test_student_with_own_email_receives_it_directly(): void
    {
        $user = User::factory()->role(Role::Student)->create(['email' => 'andres@example.com']);
        $this->andres->forceFill(['user_id' => $user->id, 'birth_date' => $this->bornToday()])->save();
        $family = $this->guardianOf($this->andres);
        $family->guardian->update(['email' => 'familia@example.com']);

        $this->sendAll();

        Mail::assertSent(BirthdayGreetingMail::class, fn ($mail) => $mail->hasTo('andres@example.com'));
        Mail::assertNotSent(BirthdayGreetingMail::class, fn ($mail) => $mail->hasTo('familia@example.com'));
    }

    public function test_student_without_email_gets_it_through_the_primary_guardian(): void
    {
        $this->lucia->update(['birth_date' => $this->bornToday(8)]);
        $other = $this->guardianOf();
        $other->guardian->update(['email' => 'segundo@example.com']);
        $this->lucia->guardians()->attach($other->guardian->id, ['relationship' => 'father', 'is_primary' => false]);
        $primary = $this->guardianOf();
        $primary->guardian->update(['email' => 'principal@example.com', 'first_names' => 'Marta']);
        $this->lucia->guardians()->attach($primary->guardian->id, ['relationship' => 'mother', 'is_primary' => true]);

        $this->sendAll();

        Mail::assertSent(BirthdayGreetingMail::class, 1);
        Mail::assertSent(BirthdayGreetingMail::class, function ($mail) {
            $html = $mail->render();

            return $mail->hasTo('principal@example.com')
                && str_contains($html, 'Hola, Marta')
                && str_contains($html, 'Lucía Pérez Díaz');
        });
    }

    public function test_the_card_never_shows_the_age(): void
    {
        $this->teacher->update(['birth_date' => $this->bornToday(47), 'email' => 'edgar@example.com']);

        $this->sendAll();

        Mail::assertSent(BirthdayGreetingMail::class, function ($mail) {
            // Sin enlaces ni imágenes: la firma o la imagen podrían traer esos dígitos por azar
            $html = preg_replace('/(href|src)="[^"]*"/', '', $mail->render());

            return str_contains($html, '¡Feliz cumpleaños!')
                && str_contains($html, 'Edgar Ojoalegre')
                && ! str_contains($html, '47')
                && ! str_contains($html, (string) today()->subYears(47)->year);
        });
    }

    public function test_the_card_embeds_the_logo_and_the_illustration(): void
    {
        $this->teacher->update(['birth_date' => $this->bornToday(40), 'email' => 'edgar@example.com']);

        $this->sendAll();

        Mail::assertSent(BirthdayGreetingMail::class, function ($mail) {
            $html = $mail->render();

            // Incrustadas, para que no dependan del dominio; el texto sigue siendo texto
            return substr_count($html, 'src="data:image/png') === 2
                && str_contains($html, 'alt="Escudo del')
                && str_contains($html, 'alt="Pastel de cumpleaños')
                && str_contains($html, 'Edgar Ojoalegre')
                && str_contains($html, config('school.birthdays.message'));
        });
    }

    public function test_nobody_receives_it_on_another_day_or_when_disabled(): void
    {
        $this->teacher->update(['birth_date' => today()->addDay()->subYears(40)->toDateString(), 'email' => 'edgar@example.com']);
        $this->otherTeacher->update(['birth_date' => $this->bornToday(40), 'email' => 'otro@example.com']);
        config(['school.birthdays.enabled' => false]);

        $this->sendAll();
        Mail::assertNothingSent();

        config(['school.birthdays.enabled' => true]);
        $this->sendAll();

        Mail::assertSent(BirthdayGreetingMail::class, 1);
        Mail::assertSent(BirthdayGreetingMail::class, fn ($mail) => $mail->hasTo('otro@example.com'));
    }

    public function test_withdrawn_students_and_inactive_staff_are_left_out(): void
    {
        $this->andres->update(['birth_date' => $this->bornToday()]);
        $this->andres->enrollments()->update(['status' => 'withdrawn']);
        $family = $this->guardianOf($this->andres);
        $family->guardian->update(['email' => 'familia@example.com']);
        $this->teacher->forceFill(['birth_date' => $this->bornToday(40), 'email' => 'edgar@example.com', 'is_active' => false])->save();

        $this->sendAll();

        Mail::assertNothingSent();
    }

    public function test_a_late_card_is_discarded_instead_of_sent_the_next_day(): void
    {
        $this->teacher->update(['birth_date' => $this->bornToday(40), 'email' => 'edgar@example.com']);
        Artisan::call('app:queue-birthday-greetings');

        $this->travel(1)->days();
        Artisan::call('app:deliver-family-mail');

        Mail::assertNothingSent();
        $this->assertSame(0, BirthdayGreeting::count());
    }

    public function test_cards_count_towards_the_daily_limit(): void
    {
        config(['school.family_mail.daily_limit' => 1]);
        $this->teacher->update(['birth_date' => $this->bornToday(40), 'email' => 'edgar@example.com']);
        $this->otherTeacher->update(['birth_date' => $this->bornToday(40), 'email' => 'otro@example.com']);

        $this->sendAll();

        Mail::assertSent(BirthdayGreetingMail::class, 1);
        $this->assertSame(0, FamilyMail::remainingToday());
    }

    public function test_person_can_unsubscribe_with_the_signed_link(): void
    {
        $this->teacher->update(['birth_date' => $this->bornToday(40), 'email' => 'edgar@example.com']);
        $url = URL::signedRoute('birthday-mail.unsubscribe', $this->teacher);

        $this->get(route('birthday-mail.unsubscribe', $this->teacher))->assertForbidden();
        $this->get($url)->assertOk()->assertSee('¿Dejar de recibir felicitaciones?');
        $this->assertTrue($this->teacher->refresh()->birthday_emails);

        $this->post($url)->assertOk()->assertSee('Listo');
        $this->assertFalse($this->teacher->refresh()->birthday_emails);

        $this->sendAll();
        Mail::assertNothingSent();

        // El admin puede reactivarla si cambia de opinión
        $this->actingAs($this->admin)->post(route('admin.people.accounts.birthday-emails', $this->teacher))->assertRedirect();
        $this->assertTrue($this->teacher->refresh()->birthday_emails);
    }

    public function test_guardian_that_disabled_notices_gets_no_card(): void
    {
        $this->lucia->update(['birth_date' => $this->bornToday(8)]);
        $family = $this->guardianOf($this->lucia);
        $family->guardian->update(['email' => 'familia@example.com']);
        $family->guardian->forceFill(['email_notifications' => false])->save();

        $this->sendAll();

        Mail::assertNothingSent();
        $this->assertSame(0, SentNotification::count());
    }

    public function test_admin_sees_today_upcoming_and_month_and_what_is_missing(): void
    {
        $this->lucia->update(['birth_date' => $this->bornToday(8)]);
        $this->teacher->update(['birth_date' => today()->addDays(3)->subYears(40)->toDateString(), 'email' => 'edgar@example.com']);

        $response = $this->actingAs($this->admin)->get(route('admin.people.birthdays'))->assertOk();

        $response->assertSeeInOrder(['Hoy', 'Pérez Díaz, Lucía', 'No recibe tarjeta', 'Próximos', 'Edgar Ojoalegre', 'En 3 días', 'La tarjeta le llega a su correo'])
            // Los demás no tienen fecha registrada
            ->assertSee('Sin fecha de nacimiento');

        $this->actingAs($this->admin)->get(route('admin.people.birthdays', ['tipo' => 'staff']))
            ->assertSee('Edgar Ojoalegre')
            ->assertDontSee('Pérez Díaz, Lucía');

        $otherMonth = today()->addMonths(2)->month;
        $this->actingAs($this->admin)->get(route('admin.people.birthdays', ['mes' => $otherMonth]))
            ->assertOk()
            ->assertSee('No hay cumpleaños registrados en este mes.');

        // Un mes inválido no rompe la página
        $this->actingAs($this->admin)->get(route('admin.people.birthdays', ['mes' => 99]))->assertOk();

        $this->assertSame(1, Birthdays::todayCount());
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertSee('1 cumpleaños hoy');
    }

    public function test_staff_birth_date_is_saved_and_validated(): void
    {
        $data = ['name' => $this->teacher->name, 'document_number' => '123456', 'role' => 'teacher'];

        $this->actingAs($this->admin)
            ->put(route('admin.people.staff.update', $this->teacher), $data + ['birth_date' => today()->addDay()->toDateString()])
            ->assertSessionHasErrors('birth_date');

        $this->actingAs($this->admin)
            ->put(route('admin.people.staff.update', $this->teacher), $data + ['birth_date' => '1985-06-07'])
            ->assertSessionHasNoErrors();

        $this->assertSame('1985-06-07', $this->teacher->refresh()->birth_date->toDateString());
    }
}
