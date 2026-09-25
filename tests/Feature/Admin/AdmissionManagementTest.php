<?php

namespace Tests\Feature\Admin;

use App\Enums\AdmissionStatus;
use App\Models\AdmissionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create(['name' => 'Rectoría']);
    }

    public function test_index_filters_by_status_and_searches(): void
    {
        $sofia = AdmissionRequest::factory()->create(['student_first_names' => 'Sofía', 'guardian_phone' => '+573001112233']);
        $mateo = AdmissionRequest::factory()->create(['student_first_names' => 'Mateo']);
        $mateo->changeStatus(AdmissionStatus::InReview, $this->admin);

        $this->actingAs($this->admin)
            ->get(route('admin.admissions.index', ['estado' => 'in_review']))
            ->assertOk()
            ->assertSee('Mateo')
            ->assertDontSee($sofia->code);

        $this->actingAs($this->admin)
            ->get(route('admin.admissions.index', ['q' => '300 111']))
            ->assertSee($sofia->code)
            ->assertDontSee($mateo->code);

        $this->actingAs($this->admin)
            ->get(route('admin.admissions.index', ['q' => $mateo->code]))
            ->assertSee('Mateo')
            ->assertDontSee($sofia->code);
    }

    public function test_detail_shows_contact_data_and_consent(): void
    {
        $admission = AdmissionRequest::factory()->create([
            'guardian_name' => 'Marta Díaz',
            'guardian_phone' => '+573217975579',
            'phone_has_whatsapp' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.admissions.show', $admission))
            ->assertOk()
            ->assertSee('Marta Díaz')
            ->assertSee('+57 321 797 5579')
            ->assertSee('https://wa.me/573217975579', false)
            ->assertSee('Autorización de datos');
    }

    public function test_admin_changes_status_and_history_records_who_and_why(): void
    {
        $admission = AdmissionRequest::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.admissions.status', $admission), [
                'status' => 'in_review',
                'note' => 'Se llamó a la familia.',
            ])
            ->assertRedirect(route('admin.admissions.show', $admission))
            ->assertSessionHas('status_message', 'Estado actualizado a «En revisión».');

        $admission->refresh();
        $change = $admission->statusChanges()->sole();

        $this->assertSame(AdmissionStatus::InReview, $admission->status);
        $this->assertSame(AdmissionStatus::Received, $change->from_status);
        $this->assertSame(AdmissionStatus::InReview, $change->to_status);
        $this->assertSame($this->admin->id, $change->changed_by);
        $this->assertSame('Se llamó a la familia.', $change->note);

        $this->actingAs($this->admin)
            ->get(route('admin.admissions.show', $admission))
            ->assertSee('Se llamó a la familia.')
            ->assertSee('Rectoría');
    }

    public function test_scheduling_an_interview_requires_date_and_saves_it(): void
    {
        $admission = AdmissionRequest::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.admissions.status', $admission), ['status' => 'interview_scheduled'])
            ->assertSessionHasErrors(['interview_at' => 'Indica la fecha y la hora de la entrevista.']);

        $this->actingAs($this->admin)
            ->put(route('admin.admissions.status', $admission), [
                'status' => 'interview_scheduled',
                'interview_at' => '2026-10-06T08:30',
            ])
            ->assertSessionHasNoErrors();

        $admission->refresh();
        $this->assertSame(AdmissionStatus::InterviewScheduled, $admission->status);
        $this->assertSame('2026-10-06 08:30', $admission->interview_at->format('Y-m-d H:i'));

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertSee($admission->studentFullName());
    }

    public function test_cannot_set_the_same_or_an_invalid_status(): void
    {
        $admission = AdmissionRequest::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.admissions.status', $admission), ['status' => 'received'])
            ->assertSessionHasErrors(['status' => 'La solicitud ya está en ese estado.']);

        $this->actingAs($this->admin)
            ->put(route('admin.admissions.status', $admission), ['status' => 'aprobada'])
            ->assertSessionHasErrors('status');

        $this->assertSame(0, $admission->statusChanges()->count());
    }

    public function test_dashboard_counts_new_requests(): void
    {
        AdmissionRequest::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertSee('3 solicitudes nuevas');
    }
}
