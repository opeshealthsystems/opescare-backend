<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\CareFacility;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CareMapAdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $staffUser;
    private User $patientUser;
    private CareFacility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'platform_admin'],
            ['label' => 'Platform Admin']
        );
        $staffRole = Role::firstOrCreate(
            ['name' => 'nurse'],
            ['label' => 'Nurse']
        );
        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['label' => 'Patient']
        );

        // Create users with web guard (session auth)
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->staffUser = User::factory()->create(['role_id' => $staffRole->id]);
        $this->patientUser = User::factory()->create(['role_id' => $patientRole->id]);

        // Create a test facility
        $this->facility = CareFacility::create([
            'facility_name' => 'Test Facility',
            'facility_type' => 'hospital',
            'listing_status' => 'pending_verification',
            'verification_status' => 'unverified',
            'region' => 'Test Region',
            'city' => 'Test City',
            'address' => '123 Test Street',
            'phone_primary' => '555-0000',
        ]);
    }

    public function test_admin_user_can_verify_facility(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/care-map/admin/facilities/{$this->facility->id}/verify", [
                'status' => 'license_verified',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }

    public function test_admin_user_can_suspend_facility(): void
    {
        $this->facility->update(['listing_status' => 'active']);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/care-map/admin/facilities/{$this->facility->id}/suspend");

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }

    public function test_staff_user_cannot_verify_facility(): void
    {
        $response = $this->actingAs($this->staffUser)
            ->postJson("/api/v1/care-map/admin/facilities/{$this->facility->id}/verify", [
                'status' => 'license_verified',
            ]);

        $response->assertStatus(403);
    }

    public function test_staff_user_cannot_suspend_facility(): void
    {
        $response = $this->actingAs($this->staffUser)
            ->postJson("/api/v1/care-map/admin/facilities/{$this->facility->id}/suspend");

        $response->assertStatus(403);
    }

    public function test_patient_user_cannot_verify_facility(): void
    {
        $response = $this->actingAs($this->patientUser)
            ->postJson("/api/v1/care-map/admin/facilities/{$this->facility->id}/verify", [
                'status' => 'license_verified',
            ]);

        $response->assertStatus(403);
    }

    public function test_patient_user_cannot_suspend_facility(): void
    {
        $response = $this->actingAs($this->patientUser)
            ->postJson("/api/v1/care-map/admin/facilities/{$this->facility->id}/suspend");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_verify_facility(): void
    {
        $response = $this->postJson("/api/v1/care-map/admin/facilities/{$this->facility->id}/verify", [
            'status' => 'license_verified',
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_suspend_facility(): void
    {
        $response = $this->postJson("/api/v1/care-map/admin/facilities/{$this->facility->id}/suspend");

        $response->assertStatus(401);
    }
}
