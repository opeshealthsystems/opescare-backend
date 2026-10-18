<?php

namespace Tests\Feature\Staff;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /facility must not link a role to a desk it cannot open: the lab technician
 * holds no `queue` capability, so no link to /facility/queue (403 for them)
 * may render on their dashboard. UI audit 2026-10-18, P1-2.
 */
class StaffPortalRoleLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function staff(string $roleName): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility = Facility::factory()->create(['type' => 'hospital']);
        $user     = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active']);

        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    public function test_a_labtech_dashboard_has_no_link_to_the_queue_desk(): void
    {
        [$tech, $facility] = $this->staff('labtech');
        $html = $this->actingAs($tech)->withSession(['active_facility_id' => $facility->id])
            ->get('/facility')->assertOk()->getContent();

        $this->assertStringNotContainsString(route('portals.queue.index') . '"', $html);
        $this->actingAs($tech)->withSession(['active_facility_id' => $facility->id])
            ->get('/facility/queue')->assertForbidden();
    }

    public function test_a_doctor_still_gets_the_queue_link(): void
    {
        [$doctor, $facility] = $this->staff('doctor');
        $html = $this->actingAs($doctor)->withSession(['active_facility_id' => $facility->id])
            ->get('/facility')->assertOk()->getContent();

        $this->assertStringContainsString(route('portals.queue.index') . '"', $html);
    }
}
