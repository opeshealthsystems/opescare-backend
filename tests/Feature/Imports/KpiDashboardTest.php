<?php

namespace Tests\Feature\Imports;

use App\Models\Appointment;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\MetricSnapshot;
use App\Models\Patient;
use App\Models\ReportExport;
use App\Models\Role;
use App\Models\User;
use App\Modules\Analytics\Services\ProductAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Group C import: platform KPI dashboard at /admin/kpi — platform tier only,
 * aggregates only, CSV export without patient identifiers.
 */
class KpiDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function platformAdmin(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', 'super_admin')->firstOrFail()->id,
            'status'  => 'active',
        ]);
    }

    private function facilityAdmin(): array
    {
        $role     = Role::where('name', 'facility_admin')->firstOrFail();
        $facility = Facility::factory()->create(['type' => 'hospital']);
        $user     = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active']);
        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    public function test_platform_admin_sees_the_kpi_dashboard_and_trend(): void
    {
        $admin = $this->platformAdmin();

        $this->actingAs($admin)->withSession(['mfa.verified' => true])
            ->get(route('portals.admin.kpi.index'))
            ->assertOk()
            ->assertSee(__('kpi.export_csv'));

        $this->actingAs($admin)->withSession(['mfa.verified' => true])
            ->get(route('portals.admin.kpi.trend', ['metric' => 'appointment_no_show_rate']))
            ->assertOk();
    }

    public function test_facility_admin_is_refused(): void
    {
        [$user, $facility] = $this->facilityAdmin();

        foreach (['portals.admin.kpi.index', 'portals.admin.kpi.trend', 'portals.admin.kpi.export'] as $name) {
            $this->actingAs($user)->withSession(['active_facility_id' => $facility->id, 'mfa.verified' => true])
                ->get(route($name))
                ->assertForbidden();
        }
    }

    public function test_the_csv_export_holds_aggregates_and_no_patient_identifiers(): void
    {
        $facility = Facility::factory()->create(['type' => 'hospital']);
        $patient  = Patient::factory()->create([
            'is_demo'    => (bool) config('demo.enabled'),
            'first_name' => 'Ngozika',
            'last_name'  => 'Mbarga',
        ]);
        Appointment::factory()->create([
            'patient_id' => $patient->id, 'facility_id' => $facility->id,
            'status' => 'no_show', 'scheduled_at' => Carbon::today()->setTime(10, 0),
        ]);

        $analytics = app(ProductAnalyticsService::class);
        $analytics->seedCoreMetrics();
        $analytics->computeDailySnapshots($facility->id, Carbon::today());
        $this->assertTrue(MetricSnapshot::exists());

        $response = $this->actingAs($this->platformAdmin())->withSession(['mfa.verified' => true])
            ->get(route('portals.admin.kpi.export', [
                'period_from' => Carbon::today()->subDay()->toDateString(),
                'period_to'   => Carbon::today()->toDateString(),
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->getContent();

        $this->assertStringStartsWith('period_date,metric,category,unit,value,facilities_reporting', $csv);
        $this->assertStringContainsString('appointment_no_show_rate', $csv);
        $this->assertStringNotContainsString((string) $patient->health_id, $csv);
        $this->assertStringNotContainsString('Ngozika', $csv);
        $this->assertStringNotContainsString('Mbarga', $csv);
        $this->assertStringNotContainsString((string) $patient->id, $csv);
        $this->assertStringNotContainsStringIgnoringCase('health_id', $csv);

        $this->assertSame('ready', ReportExport::firstOrFail()->status);
    }

    public function test_the_admin_nav_links_the_dashboard(): void
    {
        $this->actingAs($this->platformAdmin())->withSession(['mfa.verified' => true])
            ->get(route('portals.admin.kpi.index'))
            ->assertSee(route('portals.admin.kpi.index'), false);
    }
}
