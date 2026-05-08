<?php

namespace Tests\Feature\Panel;

use App\Models\ReportingQuarter;
use App\Models\Sede;
use App\Models\User;
use App\Services\Reportes\ServicioTrimestresReporte;
use App\Services\Sedes\ServicioSedes;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_authenticated_users_can_see_the_survey_qr_module_on_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('QR de encuesta');
    }

    public function test_global_users_see_the_global_sede_switcher_in_the_navbar(): void
    {
        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)
            ->withSession([ServicioSedes::SESSION_SCOPE_KEY => Sede::ID_MAICAO])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Todas las sedes');
        $response->assertSee('Sede Maicao');
        $response->assertSee('Vista');
    }

    public function test_non_global_users_do_not_see_the_global_sede_switcher_in_the_navbar(): void
    {
        $leader = User::factory()->create(['rol' => User::ROLE_LIDER_PROCESO]);

        $response = $this->actingAs($leader)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Todas las sedes');
    }

    public function test_global_users_can_update_the_active_sede_scope_from_the_navbar(): void
    {
        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('sedes.scope.update'), [
            'id_sede' => Sede::ID_FONSECA,
            'redirect_to' => route('reports.general', ['id_sede' => Sede::ID_MAICAO, 'trimestre' => 2]),
        ]);

        $response->assertRedirect(route('reports.general', ['trimestre' => 2]));
        $this->assertSame(Sede::ID_FONSECA, session(ServicioSedes::SESSION_SCOPE_KEY));
    }

    public function test_super_admin_can_update_reporting_quarters_from_dashboard(): void
    {
        CarbonImmutable::setTestNow('2026-03-14 09:00:00');

        try {
            $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);

            $this->actingAs($admin)
                ->put(route('dashboard.quarters.update'), [
                    'quarters' => [
                        1 => ['start_date' => '2026-01-10', 'end_date' => '2026-03-31'],
                        2 => ['start_date' => '2026-04-01', 'end_date' => '2026-06-30'],
                        3 => ['start_date' => '2026-07-01', 'end_date' => '2026-09-30'],
                        4 => ['start_date' => '2026-10-01', 'end_date' => '2026-12-20'],
                    ],
                ])
                ->assertRedirect(route('dashboard'))
                ->assertSessionHas('quarter_status');

            $this->assertDatabaseHas('reporting_quarters', [
                'id_sede' => null,
                'year' => 2026,
                'quarter_number' => 1,
                'start_date' => '2026-01-10 00:00:00',
                'end_date' => '2026-03-31 00:00:00',
                'updated_by' => $admin->id,
            ]);

            $this->assertSame(4, ReportingQuarter::query()->where('year', 2026)->count());
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_admin_sede_can_update_reporting_quarters_for_its_own_sede(): void
    {
        CarbonImmutable::setTestNow('2026-03-14 09:00:00');

        try {
            $adminSede = User::factory()->create([
                'rol' => User::ROLE_ADMIN_SEDE,
                'id_sede' => Sede::ID_FONSECA,
            ]);

            $this->actingAs($adminSede)
                ->put(route('dashboard.quarters.update'), [
                    'quarters' => [
                        1 => ['start_date' => '2026-01-05', 'end_date' => '2026-03-20'],
                        2 => ['start_date' => '2026-04-02', 'end_date' => '2026-06-28'],
                        3 => ['start_date' => '2026-07-03', 'end_date' => '2026-09-25'],
                        4 => ['start_date' => '2026-10-04', 'end_date' => '2026-12-18'],
                    ],
                ])
                ->assertRedirect(route('dashboard'))
                ->assertSessionHas('quarter_status');

            $this->assertDatabaseHas('reporting_quarters', [
                'id_sede' => Sede::ID_FONSECA,
                'year' => 2026,
                'quarter_number' => 1,
                'start_date' => '2026-01-05 00:00:00',
                'end_date' => '2026-03-20 00:00:00',
                'updated_by' => $adminSede->id,
            ]);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_super_admin_cannot_configure_a_quarter_outside_its_three_month_range(): void
    {
        CarbonImmutable::setTestNow('2026-03-14 09:00:00');

        try {
            $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);

            $this->actingAs($admin)
                ->from(route('dashboard'))
                ->put(route('dashboard.quarters.update'), [
                    'quarters' => [
                        1 => ['start_date' => '2026-01-01', 'end_date' => '2026-05-31'],
                        2 => ['start_date' => '2026-06-01', 'end_date' => '2026-06-30'],
                        3 => ['start_date' => '2026-07-01', 'end_date' => '2026-09-30'],
                        4 => ['start_date' => '2026-10-01', 'end_date' => '2026-12-31'],
                    ],
                ])
                ->assertRedirect(route('dashboard'))
                ->assertSessionHasErrorsIn('updateQuarters', ['quarters.1.end_date']);

            $this->assertDatabaseCount('reporting_quarters', 0);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_non_super_admin_cannot_update_reporting_quarters(): void
    {
        CarbonImmutable::setTestNow('2026-03-14 09:00:00');

        try {
            $admin20 = User::factory()->create(['rol' => User::ROLE_ADMIN_2_0]);

            $this->actingAs($admin20)
                ->put(route('dashboard.quarters.update'), [
                    'quarters' => [
                        1 => ['start_date' => '2026-01-10', 'end_date' => '2026-03-31'],
                        2 => ['start_date' => '2026-04-01', 'end_date' => '2026-06-30'],
                        3 => ['start_date' => '2026-07-01', 'end_date' => '2026-09-30'],
                        4 => ['start_date' => '2026-10-01', 'end_date' => '2026-12-31'],
                    ],
                ])
                ->assertForbidden();

            $this->assertDatabaseCount('reporting_quarters', 0);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_admin_2_0_sees_general_process_and_individual_reports_in_dashboard_navigation(): void
    {
        $admin20 = User::factory()->create(['rol' => User::ROLE_ADMIN_2_0]);

        $response = $this->actingAs($admin20)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Reporte general');
        $response->assertSee('Reporte por proceso');
        $response->assertSee('Reporte individual');
    }

    public function test_dashboard_uses_sede_specific_quarter_configuration_when_scope_is_a_sede(): void
    {
        CarbonImmutable::setTestNow('2026-03-14 09:00:00');

        try {
            $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);

            ReportingQuarter::query()->create([
                'id_sede' => Sede::ID_MAICAO,
                'year' => 2026,
                'quarter_number' => 1,
                'start_date' => '2026-01-07',
                'end_date' => '2026-03-21',
                'updated_by' => $admin->id,
            ]);

            $response = $this->actingAs($admin)
                ->withSession([ServicioSedes::SESSION_SCOPE_KEY => Sede::ID_MAICAO])
                ->get(route('dashboard'));

            $response->assertOk();
            $response->assertSee('Trimestres 2026 - Sede Maicao');
            $response->assertSee('2026-01-07', false);
            $response->assertSee('2026-03-21', false);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_process_leader_sees_individual_report_in_dashboard_navigation(): void
    {
        $leaderProcess = User::factory()->create([
            'rol' => User::ROLE_LIDER_PROCESO,
            'id_proceso' => 10,
        ]);

        $response = $this->actingAs($leaderProcess)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Reporte por proceso');
        $response->assertSee('Reporte individual');
    }
}
