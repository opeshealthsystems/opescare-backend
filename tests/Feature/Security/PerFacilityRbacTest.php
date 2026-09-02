<?php
namespace Tests\Feature\Security;

use App\Models\FacilityRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerFacilityRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_facility_role_assignments_table_exists(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasTable('facility_role_assignments'),
            'facility_role_assignments table must exist'
        );
    }

    public function test_facility_role_assignment_model_exists(): void
    {
        $this->assertTrue(
            class_exists(FacilityRoleAssignment::class),
            'FacilityRoleAssignment model must exist'
        );
    }

    public function test_user_has_role_at_facility_method(): void
    {
        $user = new \App\Models\User();
        $this->assertTrue(
            method_exists($user, 'roleAtFacility'),
            'User model must have roleAtFacility() method'
        );
    }
}
