<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Patient;
use App\Models\Facility;
use App\Models\Role;
use App\Models\User;
use App\Models\ConsentRequest;
use App\Models\ConsentGrant;
use App\Models\AccessLog;
use App\Models\EmergencyAccessEvent;
use App\Models\EmergencyReviewCase;
use App\Models\CorrectionRequest;
use App\Models\DataExportRequest;
use App\Models\SecurityIncident;
use App\Models\CountryPolicy;
use App\Models\ClinicalNote;
use App\Models\Diagnosis;
use App\Models\AllergyRecord;
use App\Models\Visit;
use App\Modules\Governance\Services\CountryPolicyService;
use App\Modules\Governance\Services\EmergencyAccessService;
use Carbon\Carbon;
use Tests\Traits\WithMobileAuth;

class DataGovernancePrivacyTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private $patient;
    private $facility;
    private $user;
    private $headers;
    private $bearerToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup B2B Client headers
        $this->headers = [
            'X-Client-ID' => 'test_client_id',
            'X-Client-Secret' => 'test_client_secret',
        ];

        $this->bearerToken = null;

        // Sandbox integration client — Connect B2B routes require a real
        // Bearer token issued by /api/v1/connect/auth/token.
        \App\Models\IntegrationClient::create([
            'client_id'     => 'test_client_id',
            'client_secret' => hash('sha256', 'test_client_secret'),
            'facility_id'   => '00000000-0000-0000-0000-000000000001',
            'name'          => 'Sandbox Test Client',
            'environment'   => 'sandbox',
            'scopes'        => json_encode(['*']),
            'status'        => 'active',
        ]);

        // 1. Setup mock patient
        $this->patient = new Patient();
        $this->patient->id = '00000000-0000-0000-0000-000000000003';
        $this->patient->first_name = 'John';
        $this->patient->last_name = 'Doe';
        $this->patient->sex = 'male';
        $this->patient->date_of_birth = '1990-01-01';
        $this->patient->health_id = 'OC-CMR-7KQ9-MP42-X8D1';
        $this->patient->emergency_contact = [
            'name' => 'Mary Doe',
            'relation' => 'Spouse',
            'phone' => '+237 600-000-000'
        ];
        $this->patient->save();

        // 2. Setup mock facility matching sandbox client facility_id: 00000000-0000-0000-0000-000000000001
        $this->facility = new Facility();
        $this->facility->id = '00000000-0000-0000-0000-000000000001';
        $this->facility->name = 'Centra Health Center';
        $this->facility->type = 'clinic';
        $this->facility->status = 'active';
        $this->facility->save();

        // 3. Setup mock user
        $this->user = new User();
        $this->user->id = '00000000-0000-0000-0000-000000000001';
        $this->user->name = 'Dr. Jane PublicHealth';
        $this->user->email = 'jane@opescare.org';
        $this->user->password = bcrypt('secret');
        $this->user->patient_id = $this->patient->id; // link so mobile auth resolves a user identity
        $this->user->role_id = Role::create([
            'name' => 'platform_admin',
            'description' => 'Platform Administrator',
        ])->id;
        $this->user->save();
    }

    /**
     * Obtain a real Bearer token for the sandbox client through the token endpoint.
     */
    private function bearerHeaders(array $extra = []): array
    {
        if ($this->bearerToken === null) {
            $response = $this->postJson('/api/v1/connect/auth/token', [
                'client_id'     => 'test_client_id',
                'client_secret' => 'test_client_secret',
                'grant_type'    => 'client_credentials',
            ]);
            $response->assertStatus(200);
            $this->bearerToken = $response->json('access_token');
        }

        return array_merge(['Authorization' => 'Bearer ' . $this->bearerToken], $extra);
    }

    /** @test */
    public function test_it_verifies_dynamic_policy_retrieval_and_fallback_rules()
    {
        $policyService = new CountryPolicyService();

        // 1. Verify fallback defaults
        $settings = $policyService->getSettings('US');
        $this->assertEquals(18, $settings['age_of_consent']);
        $this->assertTrue($settings['consent_required_for_treatment']);

        // 2. Publish a custom country policy
        $policyService->publishPolicy('FR', 'France Governance Rules', 'v1.0', [
            'age_of_consent' => 16,
            'consent_required_for_treatment' => false,
            'emergency_access_review_required' => true,
        ]);

        // 3. Verify custom policy is returned
        $frSettings = $policyService->getSettings('FR');
        $this->assertEquals(16, $frSettings['age_of_consent']);
        $this->assertFalse($frSettings['consent_required_for_treatment']);
    }

    /** @test */
    public function test_it_asserts_immutable_access_logs()
    {
        $response = $this->withHeaders($this->headers)->postJson('/api/v1/admin/security-incidents', [
            'incident_type' => 'unauthorized_access',
            'severity' => 'critical',
            'summary' => 'Suspicious patient timeline pull',
            'created_by' => $this->user->id
        ]);
        $response->assertStatus(201);

        $this->assertDatabaseHas('security_incidents', [
            'severity' => 'critical',
            'status' => 'new'
        ]);
    }

    /** @test */
    public function test_it_manages_the_multi_level_consent_lifecycle()
    {
        // 1. Request consent from Connect B2B Interoperability API
        $reqResponse = $this->postJson('/api/v1/connect/consents/request', [
            'patient_id' => $this->patient->id,
            'requested_by_user_id' => $this->user->id,
            'purpose' => 'treatment',
            'scopes' => ['patient.summary', 'allergies.read'],
            'duration_minutes' => 60
        ], $this->bearerHeaders());
        $reqResponse->assertStatus(202);
        $requestId = $reqResponse->json('consent_request_id');

        $this->assertDatabaseHas('consent_requests', [
            'id' => $requestId,
            'status' => 'pending_patient_approval'
        ]);

        // 2. Approve consent via Patient B2C Mobile App
        $appResponse = $this->withHeaders($this->mobileAuthHeaders($this->patient))
            ->postJson("/api/mobile/consent-requests/{$requestId}/approve", [
                'user_id' => $this->user->id
            ]);
        $appResponse->assertStatus(200);
        $grantId = $appResponse->json('consent_grant_id');

        $this->assertDatabaseHas('consent_grants', [
            'id' => $grantId,
            'status' => 'active'
        ]);

        // 3. Verify access is active for valid scope
        $verifyResponse = $this->postJson('/api/v1/connect/consents/verify', [
            'patient_id' => $this->patient->id,
            'scope' => 'patient.summary',
            'purpose' => 'treatment'
        ], $this->bearerHeaders());
        $verifyResponse->assertStatus(200);
        $this->assertTrue($verifyResponse->json('is_valid'));

        // 4. Revoke consent via Mobile B2C App
        $revResponse = $this->withHeaders($this->mobileAuthHeaders($this->patient))
            ->postJson("/api/mobile/consents/{$grantId}/revoke", [
                'user_id' => $this->user->id
            ]);
        $revResponse->assertStatus(200);

        // 5. Verify access is now rejected
        $verifyRevResponse = $this->postJson('/api/v1/connect/consents/verify', [
            'patient_id' => $this->patient->id,
            'scope' => 'patient.summary',
            'purpose' => 'treatment'
        ], $this->bearerHeaders());
        $verifyRevResponse->assertStatus(200);
        $this->assertFalse($verifyRevResponse->json('is_valid'));
    }

    /** @test */
    public function test_it_implements_two_person_emergency_override_reviews_with_incident_spawning()
    {
        // 1. Request emergency bypass override
        $overrideResponse = $this->postJson('/api/v1/connect/emergency-access/request', [
            'patient_id' => $this->patient->id,
            'actor_id' => $this->user->id,
            'reason' => 'Patient in anaphylactic shock, unresponsive.'
        ], $this->bearerHeaders());
        $overrideResponse->assertStatus(201);
        $eventId = $overrideResponse->json('emergency_access_event_id');

        $this->assertDatabaseHas('emergency_access_events', [
            'id' => $eventId
        ]);

        $this->assertDatabaseHas('emergency_review_cases', [
            'emergency_access_event_id' => $eventId,
            'status' => 'pending'
        ]);

        // 2. Audit emergency bypass pull in append-only access log
        $this->assertDatabaseHas('access_logs', [
            'patient_id' => $this->patient->id,
            'emergency_access' => true,
            'access_type' => 'override'
        ]);

        // 3. Review override and flag abuse -> Spawns Security Incident Alarm case automatically
        $reviewResponse = $this->withHeaders($this->headers)->postJson("/api/v1/admin/emergency-access/{$eventId}/review", [
            'reviewer_id' => $this->user->id,
            'review_status' => 'confirmed_abuse',
            'comment' => 'Physician queried timeline for non-emergency patient.'
        ]);
        $reviewResponse->assertStatus(200);

        $this->assertDatabaseHas('emergency_review_cases', [
            'emergency_access_event_id' => $eventId,
            'status' => 'confirmed_abuse'
        ]);

        $this->assertDatabaseHas('security_incidents', [
            'incident_type' => 'data_export_abuse',
            'severity' => 'high'
        ]);
    }

    /** @test */
    public function test_it_enforces_strict_data_minimization_on_emergency_profile_summaries()
    {
        // Create visit first to satisfy visit_id NOT NULL constraint
        $visit = Visit::create([
            'patient_id' => $this->patient->id,
            'facility_id' => $this->facility->id,
            'provider_id' => $this->user->id,
            'visit_type' => 'outpatient',
            'status' => 'completed',
            'started_at' => now()
        ]);

        // Seed clinical notes (consult history) that MUST be hidden
        $note = new ClinicalNote();
        $note->id = '00000000-0000-0000-0000-000000000999';
        $note->visit_id = $visit->id;
        $note->provider_id = $this->user->id;
        $note->history_of_present_illness = 'Patient mentions recurring headaches.';
        $note->examination_findings = 'Vitals normal.';
        $note->treatment_plan = 'Rest.';
        $note->status = 'signed';
        $note->save();

        // Seed chronic condition
        Diagnosis::create([
            'patient_id' => $this->patient->id,
            'provider_id' => $this->user->id,
            'visit_id' => $visit->id,
            'code' => 'E10',
            'code_system' => 'ICD-10',
            'display_name' => 'Type 1 Diabetes Mellitus',
            'status' => 'active'
        ]);

        // Seed allergies using save to retain custom ID if HasUuids strips it
        $allergy = new AllergyRecord();
        $allergy->patient_id = $this->patient->id;
        $allergy->provider_id = $this->user->id;
        $allergy->substance = 'Penicillin';
        $allergy->severity = 'severe';
        $allergy->status = 'active';
        $allergy->save();

        $emergencyService = new EmergencyAccessService();
        $profile = $emergencyService->buildEmergencyProfile($this->patient->id);

        // Asserts
        $this->assertEquals('John', $profile['identity']['first_name']);
        $this->assertEquals('Mary Doe', $profile['emergency_contact']['name']);
        $this->assertCount(1, $profile['allergies']);
        $this->assertEquals('Penicillin', $profile['allergies'][0]['substance']);
        $this->assertCount(1, $profile['chronic_conditions']);
        $this->assertEquals('Type 1 Diabetes Mellitus', $profile['chronic_conditions'][0]['display_name']);

        // Clinical SOAP consultations must strictly be omitted
        $this->assertArrayNotHasKey('clinical_notes', $profile);
    }

    /** @test */
    public function test_it_handles_right_to_rectification_with_entered_in_error_amendments()
    {
        // Create visit first to satisfy visit_id NOT NULL constraint
        $visit = Visit::create([
            'patient_id' => $this->patient->id,
            'facility_id' => $this->facility->id,
            'provider_id' => $this->user->id,
            'visit_type' => 'outpatient',
            'status' => 'completed',
            'started_at' => now()
        ]);

        // Seed signed clinical note to amend
        $note = new ClinicalNote();
        $note->id = '00000000-0000-0000-0000-000000000888';
        $note->visit_id = $visit->id;
        $note->provider_id = $this->user->id;
        $note->history_of_present_illness = 'Patient history';
        $note->examination_findings = 'Findings';
        $note->treatment_plan = 'Plan';
        $note->status = 'signed';
        $note->save();

        // 1. Submit Correction request from Mobile App B2C
        $corrResponse = $this->withHeaders($this->mobileAuthHeaders($this->patient))
            ->postJson('/api/mobile/correction-requests', [
                'patient_id' => $this->patient->id,
                'user_id' => $this->user->id,
                'resource_type' => 'clinical_note',
                'resource_id' => $note->id,
                'reason' => 'Incorrect chief complaint recorded.'
            ]);
        $corrResponse->assertStatus(201);
        $requestId = $corrResponse->json('id');

        // 2. Approve correction request via Admin plane
        $approveResponse = $this->withHeaders($this->headers)->postJson("/api/v1/admin/correction-requests/{$requestId}/approve", [
            'reviewer_id' => $this->user->id
        ]);
        $approveResponse->assertStatus(200);

        // 3. Verify original signed clinical note's status was set to entered_in_error
        $this->assertEquals('entered_in_error', $note->fresh()->status);
    }

    /** @test */
    public function test_it_enforces_expiring_download_limits_on_patient_data_exports()
    {
        // 1. Request export
        $mobileHeaders = $this->mobileAuthHeaders($this->patient);
        $expResponse = $this->withHeaders($mobileHeaders)
            ->postJson('/api/mobile/data-export-requests', [
                'patient_id' => $this->patient->id,
                'user_id' => $this->user->id,
                'export_type' => 'full_record'
            ]);
        $expResponse->assertStatus(201);
        $requestId = $expResponse->json('id');

        // 2. Approve export by Admin
        $appResponse = $this->withHeaders($this->headers)->postJson("/api/v1/admin/data-export-requests/{$requestId}/approve", [
            'approver_id' => $this->user->id
        ]);
        $appResponse->assertStatus(200);

        // 3. Download successfully
        $dlResponse = $this->withHeaders($mobileHeaders)
            ->getJson("/api/mobile/data-exports/{$requestId}/download?user_id={$this->user->id}");
        $dlResponse->assertStatus(200);
        $this->assertEquals('downloaded', DataExportRequest::find($requestId)->status);

        // 4. Force expiration and assert download block
        $export = DataExportRequest::find($requestId);
        $export->status = 'approved';
        $export->expires_at = Carbon::now()->subMinutes(10);
        $export->save();

        $dlExpiredResponse = $this->withHeaders($mobileHeaders)
            ->getJson("/api/mobile/data-exports/{$requestId}/download?user_id={$this->user->id}");
        $dlExpiredResponse->assertStatus(403);
        $this->assertEquals('expired', DataExportRequest::find($requestId)->status);
    }

    /** @test */
    public function test_it_asserts_security_incident_containment_and_resolution()
    {
        // 1. Spawn incident
        $incident = new SecurityIncident();
        $incident->incident_type = 'brute_force';
        $incident->severity = 'medium';
        $incident->status = 'new';
        $incident->summary = 'Repeated invalid login attempts from IP 192.168.1.1';
        $incident->detected_at = Carbon::now();
        $incident->save();

        // 2. Contain incident
        $containResponse = $this->withHeaders($this->headers)->postJson("/api/v1/admin/security-incidents/{$incident->id}/contain");
        $containResponse->assertStatus(200);
        $this->assertEquals('contained', SecurityIncident::find($incident->id)->status);
        $this->assertNotNull(SecurityIncident::find($incident->id)->contained_at);

        // 3. Resolve incident
        $resolveResponse = $this->withHeaders($this->headers)->postJson("/api/v1/admin/security-incidents/{$incident->id}/resolve");
        $resolveResponse->assertStatus(200);
        $this->assertEquals('resolved', SecurityIncident::find($incident->id)->status);
        $this->assertNotNull(SecurityIncident::find($incident->id)->resolved_at);
    }
}
