<?php

namespace Tests\Feature\SecurityOps;

use App\Models\Facility;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The security incidents queue must open for the whole platform compliance tier.
 *
 * It filtered `security_incidents` on a `facility_id` column that does not
 * exist, guarded by `when($facilityId && ! $isPlatformAdmin, …)` where
 * $isPlatformAdmin was a hardcoded list of three role names. So the page threw
 * SQLSTATE[42703] for any user who held a facility and was not one of those
 * three — privacy_officer, data_protection_officer, compliance_officer,
 * audit_reviewer and emergency_access_reviewer are all in that gap, and all of
 * them are explicitly allowed into /portals/admin/security.
 *
 * It stayed hidden because those roles had no seeded user, so nothing ever
 * requested the page as one of them.
 */
class SecurityIncidentsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    public static function complianceTierRoles(): array
    {
        return ['privacy_officer', 'compliance_officer', 'audit_reviewer', 'security_officer', 'platform_admin'];
    }

    public function test_the_incidents_queue_opens_for_every_compliance_role(): void
    {
        $failures = [];

        foreach (self::complianceTierRoles() as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            // A facility on the user is the trigger: the old filter only fired
            // when one was present, which is why this went unnoticed.
            $user = User::factory()->create([
                'role_id'             => $role->id,
                'primary_facility_id' => Facility::factory()->create()->id,
                'status'              => 'active',
            ]);

            $res = $this->actingAs($user)->get('/portals/admin/security/incidents');

            if ($res->status() !== 200) {
                $failures[] = "{$roleName} got {$res->status()}";
            }
        }

        $this->assertNotEmpty(self::complianceTierRoles());
        $this->assertSame([], $failures, "\n" . implode("\n", $failures) . "\n");
    }

    public function test_security_incidents_are_platform_global_not_facility_scoped(): void
    {
        // Pins the schema fact the fix rests on. If incidents ever do become
        // facility-owned, this fails and the scoping decision gets made
        // deliberately rather than by reintroducing a filter on a missing
        // column.
        $this->assertFalse(
            Schema::hasColumn('security_incidents', 'facility_id'),
            'security_incidents gained a facility_id — revisit the scoping in SecurityOpsController::incidents()'
        );

        // The neighbouring emergency-access queue IS facility-scoped, and that
        // filter is correct. Guard it so the two do not get "tidied" together.
        $this->assertTrue(Schema::hasColumn('emergency_access_events', 'facility_id'));
    }
}
