<?php

namespace Tests\Feature;

use App\Enums\VolunteerArea;
use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerStatus;
use App\Mail\VolunteerApplicationReceived;
use App\Models\DonationAccount;
use App\Models\Donor;
use App\Models\Testimonial;
use App\Models\VolunteerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SupportPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Mail::fake();
    }

    private function volunteer(array $overrides = []): array
    {
        return [
            'name' => 'Luisa Martínez',
            'phone' => '321 797 5579',
            'phone_has_whatsapp' => '1',
            'email' => 'Luisa@Ejemplo.com ',
            'areas' => ['reading', 'events'],
            'availability' => 'weekends',
            'message' => 'Me gusta leer cuentos.',
            'privacy' => '1',
            ...$overrides,
        ];
    }

    public function test_without_data_invites_to_write_and_hides_allies(): void
    {
        $this->get(route('support'))
            ->assertOk()
            ->assertSee('Escríbenos y te contamos cómo puedes aportar al colegio.')
            ->assertSee('mailto:cecgenesis16@gmail.com?subject=', false)
            ->assertDontSee('data-copy', false)
            ->assertDontSee('id="aliados"', false)
            ->assertSee('Quiero ser voluntario');
    }

    public function test_shows_visible_accounts_donors_and_testimonials_in_order(): void
    {
        DonationAccount::factory()->create(['label' => 'Segunda cuenta', 'number' => '222', 'position' => 2]);
        DonationAccount::factory()->create(['label' => 'Primera cuenta', 'number' => '111', 'position' => 1]);
        DonationAccount::factory()->create(['label' => 'Cuenta oculta', 'is_visible' => false]);
        Donor::factory()->create(['name' => 'Ferretería aliada', 'website' => 'https://ejemplo.com']);
        Donor::factory()->create(['name' => 'Donante oculto', 'is_visible' => false]);
        Testimonial::factory()->create(['quote' => 'Valió cada brochazo', 'author' => 'Ana', 'role' => 'acudiente']);

        $this->get(route('support'))
            ->assertSeeInOrder(['Primera cuenta', 'Segunda cuenta'])
            ->assertSee('data-copy="111"', false)
            ->assertDontSee('Cuenta oculta')
            ->assertSee('Ferretería aliada')
            ->assertSee('rel="noopener nofollow"', false)
            ->assertDontSee('Donante oculto')
            ->assertSee('“Valió cada brochazo”', false)
            ->assertSee('Ana, acudiente');
    }

    public function test_home_uses_real_support_data(): void
    {
        DonationAccount::factory()->create(['number' => '123-456']);
        Testimonial::factory()->create(['quote' => 'Testimonio real', 'author' => 'Ana', 'role' => null]);
        Donor::factory()->create(['name' => 'Aliado real']);

        $this->get(route('home'))
            ->assertSee('data-copy="123-456"', false)
            ->assertSee('Testimonio real')
            ->assertSee('Aliado real')
            ->assertDontSee('000-000000-00');
    }

    public function test_volunteer_application_is_saved_and_school_is_notified(): void
    {
        $this->post(route('support.volunteer'), $this->volunteer())
            ->assertRedirect(route('support').'#voluntariado')
            ->assertSessionHas('volunteer_name', 'Luisa');

        $application = VolunteerApplication::sole();
        $this->assertSame('+573217975579', $application->phone);
        $this->assertSame('luisa@ejemplo.com', $application->email);
        $this->assertEquals([VolunteerArea::Reading, VolunteerArea::Events], $application->areas->all());
        $this->assertSame(VolunteerAvailability::Weekends, $application->availability);
        $this->assertSame(VolunteerStatus::New, $application->fresh()->status);
        $this->assertNotNull($application->privacy_accepted_at);

        Mail::assertQueued(VolunteerApplicationReceived::class, fn ($mail) => $mail->hasTo('cecgenesis16@gmail.com'));

        $this->get(route('support'))->assertSee('¡Gracias, Luisa!')->assertDontSee('name="privacy"', false);
    }

    public function test_volunteer_validation(): void
    {
        $this->from(route('support'))->post(route('support.volunteer'), $this->volunteer([
            'phone' => '12345',
            'areas' => [],
            'availability' => '',
            'privacy' => '',
        ]))->assertSessionHasErrors([
            'phone' => 'Escribe un celular de 10 dígitos que empiece por 3 (o un fijo que empiece por 60).',
            'areas' => 'Elige al menos una forma de ayudar.',
            'availability' => 'Cuéntanos cuándo puedes ayudar.',
            'privacy' => 'Para enviar tus datos debes autorizar su tratamiento.',
        ]);

        $this->post(route('support.volunteer'), $this->volunteer(['areas' => ['hackear']]))
            ->assertSessionHasErrors(['areas.0']);

        $this->assertSame(0, VolunteerApplication::count());
        Mail::assertNothingQueued();
    }

    public function test_honeypot_pretends_success_without_saving(): void
    {
        $this->post(route('support.volunteer'), $this->volunteer(['website' => 'http://spam.test']))
            ->assertSessionHas('volunteer_name');

        $this->assertSame(0, VolunteerApplication::count());
        Mail::assertNothingQueued();
    }

    public function test_volunteer_form_is_rate_limited(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post(route('support.volunteer'), $this->volunteer());
        }

        $this->post(route('support.volunteer'), $this->volunteer())->assertStatus(429);
        $this->assertSame(5, VolunteerApplication::count());
    }

    public function test_privacy_policy_covers_volunteering(): void
    {
        $this->get(route('privacy'))->assertSee('Contactar a quienes se ofrecen como voluntarios');
    }
}
