<?php

namespace Tests\Feature\Facility;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\HealthIdVerificationEvent;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /admin for a facility admin is that facility's admin home — titled with the
 * facility's name, with a Health ID entry point in the sidebar and a "Get
 * started" checklist driven only by real rows. Platform admins keep the
 * governance portal title and never see the checklist.
 */
class FacilityAdminHomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withSession(['mfa.verified' => true]);
        config(['demo.enabled' => false]);
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function user(string $roleName, ?Facility $facility): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id, 'primary_facility_id' => $facility?->id, 'status' => 'active',
        ]);
        if ($facility) {
            FacilityRoleAssignment::create([
                'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
                'is_active' => true, 'assigned_at' => now(),
            ]);
        }

        return $user;
    }

    private function home(User $admin, Facility $facility): string
    {
        return $this->actingAs($admin)->withSession(['active_facility_id' => $facility->id])
            ->get('/admin')->assertOk()->getContent();
    }

    private function step(string $html, string $key): ?string
    {
        return preg_match('/data-step="' . $key . '" data-done="([01])"/', $html, $m) ? $m[1] : null;
    }

    public function test_facility_admin_sees_their_facility_title_not_the_governance_portal(): void
    {
        $facility = Facility::factory()->create(['type' => 'hospital', 'name' => 'Hôpital Laquintinie']);
        $html = $this->home($this->user('facility_admin', $facility), $facility);

        $this->assertStringContainsString(e('Hôpital Laquintinie — Facility admin'), $html);
        $this->assertStringNotContainsString('Admin Governance Portal', $html);
    }

    public function test_platform_admin_keeps_the_governance_title_and_no_checklist(): void
    {
        $html = $this->actingAs($this->user('platform_admin', null))->get('/admin')->assertOk()->getContent();

        $this->assertStringContainsString('Admin Governance Portal', $html);
        $this->assertStringNotContainsString('Facility admin', $html);
        $this->assertStringNotContainsString('data-testid="get-started"', $html);
    }

    public function test_facility_admin_sidebar_links_verify_a_health_id(): void
    {
        $facility = Facility::factory()->create(['type' => 'hospital']);
        $html = $this->home($this->user('facility_admin', $facility), $facility);

        $this->assertStringContainsString('href="' . route('verify.health-id') . '"', $html);
        $this->assertStringContainsString('Verify a Health ID', $html);
    }

    public function test_checklist_starts_with_every_step_open_on_a_fresh_facility(): void
    {
        $facility = Facility::factory()->create(['type' => 'hospital']);
        $html = $this->home($this->user('facility_admin', $facility), $facility);

        $this->assertStringContainsString('data-testid="get-started"', $html);
        $this->assertSame('0', $this->step($html, 'team'));
        $this->assertSame('0', $this->step($html, 'profile'));
        $this->assertSame('0', $this->step($html, 'verify'));
        $this->assertStringContainsString('Doctors and nurses open patient records', $html);
    }

    public function test_checklist_ticks_steps_from_real_rows_and_hides_when_all_done(): void
    {
        $facility = Facility::factory()->create(['type' => 'hospital']);
        $admin = $this->user('facility_admin', $facility);

        // Another facility's staff and verifications never count here.
        $other = Facility::factory()->create(['type' => 'hospital']);
        $this->user('doctor', $other);

        $doctor = $this->user('doctor', $facility);
        $html = $this->home($admin, $facility);
        $this->assertSame('1', $this->step($html, 'team'));
        $this->assertSame('0', $this->step($html, 'profile'));

        CareFacility::create([
            'facility_id' => $facility->id, 'facility_name' => $facility->name, 'facility_type' => 'hospital',
            'listing_status' => 'active', 'country_code' => 'CM', 'region' => 'Littoral', 'city' => 'Douala',
            'address' => 'Bonanjo', 'latitude' => 4.05, 'longitude' => 9.7, 'phone_primary' => '+237 233 42 00 00',
        ]);
        $html = $this->home($admin, $facility);
        $this->assertSame('1', $this->step($html, 'profile'));
        $this->assertSame('0', $this->step($html, 'verify'));

        HealthIdVerificationEvent::create([
            'patient_id' => Patient::factory()->create(['facility_id' => $facility->id])->id,
            'verification_type' => 'qr_scan', 'verified_by_user_id' => $doctor->id,
            'verified_by_facility_id' => null, 'verification_status' => 'verified',
        ]);
        $html = $this->home($admin, $facility);
        $this->assertStringNotContainsString('data-testid="get-started"', $html);
    }

    public function test_french_title(): void
    {
        $facility = Facility::factory()->create(['type' => 'hospital', 'name' => 'Hôpital Général']);
        app()->setLocale('fr');
        $this->assertSame("Hôpital Général — Administration de l'établissement",
            __('facility_admin_home.page_title', ['facility' => $facility->name]));
    }
}
