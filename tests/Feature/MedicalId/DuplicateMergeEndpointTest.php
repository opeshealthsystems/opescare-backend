<?php

namespace Tests\Feature\MedicalId;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Patient;
use App\Models\IdentityMergeCase;
use App\Models\HealthIdAlias;
use App\Models\MedicalIdAccessEvent;
use Illuminate\Support\Str;

class DuplicateMergeEndpointTest extends TestCase
{
    use RefreshDatabase;

    private Patient $primary;
    private Patient $secondary;
    private IdentityMergeCase $mergeCase;

    /** Integration client headers required by VerifyIntegrationClient middleware */
    private array $clientHeaders = [
        'X-Client-ID'     => 'test_client_id',
        'X-Client-Secret' => 'test_client_secret',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->primary = Patient::create([
            'first_name' => 'Demo',
            'last_name' => 'Primary',
            'country_code' => 'CM',
            'health_id' => 'CM-HID-1111-2222-3333',
            'verification_status' => 'facility_verified',
            'identity_status' => 'verified',
            'is_demo' => false
        ]);

        $this->secondary = Patient::create([
            'first_name' => 'Demo',
            'last_name' => 'Secondary',
            'country_code' => 'CM',
            'health_id' => 'CM-HID-4444-5555-6666',
            'verification_status' => 'duplicate_suspected',
            'identity_status' => 'unverified',
            'is_demo' => false
        ]);

        $this->mergeCase = IdentityMergeCase::create([
            'uuid' => Str::uuid(),
            'primary_patient_id' => $this->primary->id,
            'secondary_patient_id' => $this->secondary->id,
            'status' => 'pending_review',
            'match_score' => 95.0,
        ]);
    }

    public function test_can_list_pending_merge_cases()
    {
        $response = $this->withHeaders($this->clientHeaders)->getJson('/api/v1/connect/admin/merge-cases');

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonStructure([
                     'cases' => [
                         '*' => [
                             'uuid',
                             'status',
                             'primary_patient',
                             'secondary_patient'
                         ]
                     ]
                 ]);
    }

    public function test_can_approve_merge_case_and_create_alias()
    {
        $response = $this->withHeaders($this->clientHeaders)->postJson('/api/v1/connect/admin/merge-cases/' . $this->mergeCase->uuid . '/resolve', [
            'resolution' => 'approve',
            'review_reason' => 'Confirmed same person based on national ID'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success');

        // Check if secondary is merged
        $this->assertDatabaseHas('patients', [
            'id' => $this->secondary->id,
            'verification_status' => 'merged',
            'identity_status' => 'merged'
        ]);

        // Check if merge case is merged
        $this->assertDatabaseHas('identity_merge_cases', [
            'uuid' => $this->mergeCase->uuid,
            'status' => 'merged'
        ]);

        // Check if alias was created
        $this->assertDatabaseHas('health_id_aliases', [
            'patient_id' => $this->primary->id,
            'alias_type' => 'merged_health_id',
            'alias_value' => 'CM-HID-4444-5555-6666',
            'status' => 'active'
        ]);

        // Check if audited
        $this->assertDatabaseHas('medical_id_access_events', [
            'patient_id' => $this->primary->id,
            'access_type' => 'approve_merge',
            'purpose' => 'identity_reconciliation'
        ]);
    }

    public function test_can_reject_merge_case()
    {
        $response = $this->withHeaders($this->clientHeaders)->postJson('/api/v1/connect/admin/merge-cases/' . $this->mergeCase->uuid . '/resolve', [
            'resolution' => 'reject',
            'review_reason' => 'Different persons'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success');

        // Check if merge case is rejected
        $this->assertDatabaseHas('identity_merge_cases', [
            'uuid' => $this->mergeCase->uuid,
            'status' => 'rejected'
        ]);

        // Check if primary and secondary are unaffected (except audit)
        $this->assertDatabaseHas('patients', [
            'id' => $this->secondary->id,
            'verification_status' => 'duplicate_suspected' // Should remain unchanged
        ]);
    }
}
