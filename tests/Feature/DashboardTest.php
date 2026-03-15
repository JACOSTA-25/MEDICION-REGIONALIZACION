<?php

namespace Tests\Feature;

use App\Models\ReportingQuarter;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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
                'year' => 2026,
                'quarter_number' => 1,
                'start_date' => '2026-01-10',
                'end_date' => '2026-03-31',
                'updated_by' => $admin->id,
            ]);

            $this->assertSame(4, ReportingQuarter::query()->where('year', 2026)->count());
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
}
