<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    private function data(array $overrides = []): array
    {
        return [
            'school_year' => '2028',
            'admissions_badge' => 'Matrículas 2028 abiertas',
            'costs' => [
                ['label' => 'Preescolar y primaria', 'enrollment' => '$ 120.000', 'monthly' => '105.000'],
                ['label' => 'Secundaria', 'enrollment' => '310000', 'monthly' => '250.500'],
            ],
            'requirements' => "- Registro civil\n\n• Fotos 3x4\n",
            'response_time' => '2 días hábiles',
            'admissions_notify_email' => 'Admisiones@Genesis.test',
            'support_notify_email' => 'voluntarios@genesis.test',
            'office_hours' => "Secretaría: lunes a viernes, 7:00 a. m. – 3:00 p. m.\n",
            'whatsapp_enabled' => '1',
            ...$overrides,
        ];
    }

    public function test_admin_changes_settings_and_the_web_reflects_them(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), $this->data())
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2028, config('school.admissions.school_year'));
        $this->assertSame(['Registro civil', 'Fotos 3x4'], config('school.admissions.requirements'));
        $this->assertSame(['preschool', 'primary'], config('school.admissions.costs.0.levels'), 'Los niveles del grupo se conservan');
        $this->assertSame('admisiones@genesis.test', config('school.admissions.notify_email'));
        $this->assertSame($this->admin->id, Setting::find('admissions.costs')->updated_by);

        $this->get(route('admissions'))
            ->assertSee('Costos 2028')
            ->assertSee('$ 120.000')
            ->assertSee('$ 250.500')
            ->assertSee('Fotos 3x4');

        $this->get(route('home'))
            ->assertSee('Matrículas 2028 abiertas')
            ->assertSee('Secretaría: lunes a viernes');
    }

    public function test_saved_settings_survive_a_new_request_cycle(): void
    {
        Setting::create(['key' => 'admissions.response_time', 'value' => '5 días hábiles']);
        Setting::create(['key' => 'no.editable', 'value' => 'ignorado']);

        Settings::apply();

        $this->assertSame('5 días hábiles', config('school.admissions.response_time'));
        $this->assertNull(config('school.no.editable'));
    }

    public function test_validation(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.update'), $this->data([
            'school_year' => '1999',
            'costs' => [['label' => '', 'enrollment' => '', 'monthly' => 'abc']],
            'requirements' => "\n\n",
            'admissions_notify_email' => 'no-es-correo',
        ]))->assertSessionHasErrors(['school_year', 'costs', 'costs.0.label', 'costs.0.enrollment', 'costs.0.monthly', 'requirements', 'admissions_notify_email']);

        $this->assertSame(0, Setting::count());
    }

    public function test_edit_page_shows_current_values(): void
    {
        $this->actingAs($this->admin)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('value="111.000"', false)
            ->assertSee('Registro civil de nacimiento del estudiante');
    }

    public static function nonAdminRoles(): array
    {
        return [[Role::Teacher], [Role::Student], [Role::Guardian]];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_only_admin_can_change_settings(Role $role): void
    {
        $user = User::factory()->role($role)->create();

        $this->actingAs($user)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($user)->put(route('admin.settings.update'), $this->data())->assertForbidden();

        $this->assertSame(0, Setting::count());
    }
}
