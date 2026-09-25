<?php

namespace Tests\Feature\Admin;

use App\Models\AdmissionRequest;
use App\Models\DonationAccount;
use App\Models\Event;
use App\Models\User;
use App\Models\VolunteerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_greets_with_what_needs_attention(): void
    {
        AdmissionRequest::factory()->count(2)->create();
        VolunteerApplication::factory()->create();
        Event::factory()->create(['title' => 'Entrega de boletines', 'starts_at' => now()->addDays(3)]);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('2 solicitudes nuevas')
            ->assertSee('1 voluntario nuevo')
            ->assertSee('Entrega de boletines');
    }

    public function test_checklist_shows_only_what_is_missing(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertSee('Completa tu sitio')
            ->assertSee('0 de 8')
            ->assertSee('Cuentas para donar en Apóyanos')
            ->assertSee('Próximos eventos en el calendario');

        DonationAccount::factory()->create();
        Event::factory()->create(['starts_at' => now()->addDays(3)]);
        config(['school.office_hours' => ['Secretaría: 7:00 a. m. – 3:00 p. m.']]);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertSee('3 de 8')
            ->assertDontSee('Cuentas para donar en Apóyanos')
            ->assertDontSee('Próximos eventos en el calendario')
            ->assertDontSee('Horarios de atención en el pie de página');
    }
}
