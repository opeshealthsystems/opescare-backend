<?php

namespace Tests\Feature\Security;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\HealthIdQrToken;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The "Active tokens" tile on the /admin landing was the one platform-wide
 * figure a facility administrator could see (HOSPITAL_ADMIN_DASHBOARD_AUDIT
 * finding (a)). health_id_qr_tokens has no facility_id, so the scope goes
 * through the patient: a facility-tier viewer counts their own patients'
 * tokens, a platform viewer counts all, and a facility-tier viewer with no
 * facility context gets no tile rather than a leaked total.
 */
class AdminPortalTokenTileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Platform admins must pass MFA (User::ALWAYS_MFA_ROLES); act as one who has.
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
            'role_id'             => $role->id,
            'primary_facility_id' => $facility?->id,
            'status'              => 'active',
        ]);
        if ($facility) {
            FacilityRoleAssignment::create([
                'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
                'is_active' => true, 'assigned_at' => now(),
            ]);
        }

        return $user;
    }

    private function token(Facility $facility): HealthIdQrToken
    {
        $patient = Patient::factory()->create(['facility_id' => $facility->id]);

        return HealthIdQrToken::create([
            'patient_id' => $patient->id,
            'lookup_id'  => substr(bin2hex(random_bytes(8)), 0, 12),
            'token_hash' => bcrypt('secret'),
            'token_type' => 'static_identity_qr',
            'status'     => 'active',
        ]);
    }

    private function tile(string $html): ?string
    {
        return preg_match('/data-kpi-value="active_tokens">([^<]*)</', $html, $m) ? trim($m[1]) : null;
    }

    public function test_a_facility_admin_counts_only_their_own_patients_tokens(): void
    {
        $mine  = Facility::factory()->create(['type' => 'hospital']);
        $other = Facility::factory()->create(['type' => 'hospital']);
        $this->token($mine);
        $this->token($other);
        $this->token($other);

        $html = $this->actingAs($this->user('facility_admin', $mine))
            ->withSession(['active_facility_id' => $mine->id])
            ->get('/admin')->assertOk()->getContent();

        $this->assertSame('1', $this->tile($html));
    }

    public function test_a_platform_admin_still_sees_the_platform_total(): void
    {
        $a = Facility::factory()->create(['type' => 'hospital']);
        $b = Facility::factory()->create(['type' => 'hospital']);
        $this->token($a);
        $this->token($b);

        $html = $this->actingAs($this->user('platform_admin', null))
            ->get('/admin')->assertOk()->getContent();

        $this->assertSame('2', $this->tile($html));
    }

    public function test_a_facility_admin_with_no_facility_context_gets_no_tile_not_a_leak(): void
    {
        $this->token(Facility::factory()->create(['type' => 'hospital']));

        $html = $this->actingAs($this->user('facility_admin', null))
            ->get('/admin')->assertOk()->getContent();

        $this->assertNull($this->tile($html));
        $this->assertStringNotContainsString('data-kpi="active_tokens"', $html);
    }
}
