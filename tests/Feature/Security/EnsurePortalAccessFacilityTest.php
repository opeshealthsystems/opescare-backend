<?php
namespace Tests\Feature\Security;

use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsurePortalAccessFacilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_role_at_facility_cannot_access_admin_portal(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $response = $this->actingAs($user)
            ->withSession(['active_facility_id' => 'some-facility-id'])
            ->get(route('portals.admin'));

        // Must be 403 — no role at this facility
        $response->assertStatus(403);
    }

    public function test_ensure_portal_access_uses_role_at_facility_method(): void
    {
        $source = file_get_contents(
            app_path('Http/Middleware/EnsurePortalAccess.php')
        );

        $this->assertTrue(
            str_contains($source, 'roleAtFacility') || str_contains($source, 'facilityRole'),
            'EnsurePortalAccess must use roleAtFacility() for per-facility role lookup'
        );
    }
}
