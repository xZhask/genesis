<?php

namespace Tests\Feature;

use App\Enums\AdmissionStatus;
use App\Mail\AdmissionRequestConfirmation;
use App\Mail\AdmissionRequestReceived;
use App\Models\AdmissionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdmissionRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Mail::fake();
        RateLimiter::clear('admissions');
    }

    private function validData(array $overrides = []): array
    {
        return [
            'student_first_names' => 'Sofía',
            'student_last_names' => 'Pérez Díaz',
            'student_birth_date' => now()->subYears(6)->toDateString(),
            'grade' => 'Transición',
            'current_school' => '',
            'guardian_name' => 'Marta Díaz',
            'guardian_relationship' => 'mother',
            'guardian_phone' => '321 797 5579',
            'phone_has_whatsapp' => '1',
            'guardian_email' => 'Marta@Example.com',
            'comments' => 'Tiene un hermano en 3.°',
            'privacy' => '1',
            'website' => '',
            ...$overrides,
        ];
    }

    public function test_page_shows_process_costs_requirements_and_form(): void
    {
        $this->get(route('admissions'))
            ->assertOk()
            ->assertSee('Año lectivo 2027')
            ->assertSee('$ 111.000')
            ->assertSee('$ 99.900')
            ->assertSee('$ 299.700')
            ->assertSee('$ 244.200')
            ->assertSee('Registro civil de nacimiento')
            ->assertSee('id="solicitud"', false)
            ->assertSeeInOrder(['Párvulos', 'Transición', '1.°', '5.°', '6.°', '9.°'])
            ->assertSee(route('privacy'), false);
    }

    public function test_a_valid_request_is_saved_as_received_and_notified(): void
    {
        $this->post(route('admissions.store'), $this->validData())
            ->assertRedirect(route('admissions.thanks'))
            ->assertSessionHas('admission_code', 'PRE-2027-0001');

        $admission = AdmissionRequest::sole();

        $this->assertSame(AdmissionStatus::Received, $admission->status);
        $this->assertSame('PRE-2027-0001', $admission->code);
        $this->assertSame(2027, $admission->school_year);
        $this->assertSame('+573217975579', $admission->guardian_phone);
        $this->assertSame('marta@example.com', $admission->guardian_email);
        $this->assertTrue($admission->phone_has_whatsapp);
        $this->assertNull($admission->current_school);
        $this->assertNotNull($admission->privacy_accepted_at);
        $this->assertSame(config('school.privacy_policy_version'), $admission->privacy_policy_version);

        Mail::assertQueued(AdmissionRequestReceived::class, fn ($mail) => $mail->hasTo('cecgenesis16@gmail.com')
            && $mail->hasReplyTo('marta@example.com'));
        Mail::assertQueued(AdmissionRequestConfirmation::class, fn ($mail) => $mail->hasTo('marta@example.com'));
    }

    public function test_confirmation_email_is_skipped_without_guardian_email(): void
    {
        $this->post(route('admissions.store'), $this->validData(['guardian_email' => '']));

        Mail::assertQueued(AdmissionRequestReceived::class);
        Mail::assertNotQueued(AdmissionRequestConfirmation::class);
    }

    public function test_thanks_page_shows_the_code_and_requires_a_submission(): void
    {
        $this->get(route('admissions.thanks'))->assertRedirect(route('admissions'));

        $this->withSession(['admission_code' => 'PRE-2027-0007'])
            ->get(route('admissions.thanks'))
            ->assertOk()
            ->assertSee('PRE-2027-0007')
            ->assertSee('3 días hábiles');
    }

    public function test_privacy_authorization_is_required(): void
    {
        $this->from(route('admissions'))
            ->post(route('admissions.store'), $this->validData(['privacy' => null]))
            ->assertRedirect(route('admissions'))
            ->assertSessionHasErrors(['privacy' => 'Para enviar la solicitud debes autorizar el tratamiento de datos.']);

        $this->assertDatabaseCount('admission_requests', 0);
    }

    public function test_invalid_data_is_rejected_with_spanish_messages(): void
    {
        $this->post(route('admissions.store'), $this->validData([
            'student_first_names' => '',
            'grade' => '11.°',
            'guardian_relationship' => 'vecino',
            'guardian_phone' => '12345',
            'guardian_email' => 'no-es-correo',
            'student_birth_date' => now()->addDay()->toDateString(),
        ]))->assertSessionHasErrors([
            'student_first_names' => 'Escribe nombres del estudiante.',
            'grade' => 'Elige un grado de la lista.',
            'guardian_relationship' => 'Elige un parentesco de la lista.',
            'guardian_phone',
            'guardian_email',
            'student_birth_date' => 'La fecha de nacimiento debe ser anterior a hoy.',
        ]);

        $this->assertDatabaseCount('admission_requests', 0);
    }

    public function test_status_cannot_be_set_from_the_form(): void
    {
        $this->post(route('admissions.store'), $this->validData(['status' => 'accepted']));

        $this->assertSame(AdmissionStatus::Received, AdmissionRequest::sole()->status);
    }

    public function test_phone_formats_are_normalized(): void
    {
        foreach (['+57 321 797 5579', '(321) 797-5579', '573217975579'] as $i => $phone) {
            $this->post(route('admissions.store'), $this->validData(['guardian_phone' => $phone]))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(['+573217975579'], AdmissionRequest::pluck('guardian_phone')->unique()->values()->all());
    }

    public function test_honeypot_submissions_are_not_saved(): void
    {
        $this->post(route('admissions.store'), $this->validData(['website' => 'http://spam.example']))
            ->assertRedirect(route('admissions.thanks'));

        $this->assertDatabaseCount('admission_requests', 0);
        Mail::assertNothingQueued();
    }

    public function test_submissions_are_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admissions.store'), $this->validData())->assertRedirect(route('admissions.thanks'));
        }

        $this->post(route('admissions.store'), $this->validData())->assertStatus(429);
        $this->assertDatabaseCount('admission_requests', 5);
    }

    public function test_codes_are_unique_per_request(): void
    {
        $codes = AdmissionRequest::factory()->count(3)->create()->pluck('code');

        $this->assertCount(3, $codes->unique());
        $this->assertSame('PRE-2027-0003', $codes->last());
    }

    public function test_emails_render_with_request_data(): void
    {
        $admission = AdmissionRequest::factory()->create([
            'student_first_names' => 'Sofía',
            'guardian_name' => 'Marta Díaz',
            'guardian_phone' => '+573217975579',
            'grade' => 'Transición',
        ]);

        (new AdmissionRequestReceived($admission))
            ->assertSeeInHtml($admission->code)
            ->assertSeeInHtml('+57 321 797 5579')
            ->assertSeeInHtml('Transición');

        (new AdmissionRequestConfirmation($admission))
            ->assertSeeInHtml('Hola, Marta Díaz')
            ->assertSeeInHtml($admission->code);
    }

    public function test_privacy_policy_page_is_published(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('Ley 1581 de 2012')
            ->assertSee('cecgenesis16@gmail.com')
            ->assertSee('Datos de niños, niñas y adolescentes');
    }
}
