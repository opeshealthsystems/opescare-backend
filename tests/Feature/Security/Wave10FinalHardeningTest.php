<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\Patient;
use App\Models\AllergyRecord;
use App\Models\Diagnosis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Wave10FinalHardeningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Return the test-bypass integration client headers.
     * The VerifyIntegrationClient middleware allows these in the testing environment.
     */
    private function clientHeaders(): array
    {
        return [
            'X-Client-ID'     => 'test_client_id',
            'X-Client-Secret' => 'test_client_secret',
        ];
    }

    public function test_emergency_profile_returns_real_allergy_data_not_hardcoded_penicillin(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);

        AllergyRecord::factory()->create([
            'patient_id' => $patient->id,
            'substance'  => 'Aspirin',
            'severity'   => 'Moderate',
            'status'     => 'active',
        ]);

        $response = $this->postJson('/api/v1/connect/patients/emergency-profile', [
            'health_id' => $patient->health_id,
            'reason'    => 'Trauma — immediate clinical need',
        ], $this->clientHeaders());

        $response->assertStatus(200);

        $allergies = $response->json('profile.allergies');
        $this->assertNotEmpty($allergies);
        $this->assertEquals('Aspirin', $allergies[0]['substance']);

        $substances = array_column($allergies, 'substance');
        $this->assertNotContains('Penicillin', $substances);
    }

    public function test_emergency_profile_blood_type_is_null_not_hardcoded(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);

        $response = $this->postJson('/api/v1/connect/patients/emergency-profile', [
            'health_id' => $patient->health_id,
            'reason'    => 'Trauma — immediate clinical need',
        ], $this->clientHeaders());

        $response->assertStatus(200);
        // blood_type is not in the patient schema — must be explicitly null,
        // not the hardcoded 'O+' that was previously fabricated.
        $this->assertNull($response->json('profile.blood_type'));
    }

    public function test_emergency_profile_returns_real_diagnosis_data_not_hardcoded_e119(): void
    {
        $patient  = Patient::factory()->create(['is_demo' => false]);
        $provider = \App\Models\User::factory()->create();
        $facility = \App\Models\Facility::factory()->create();

        // Create a visit row to satisfy the diagnoses.visit_id FK constraint
        $visitId = (string) \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('visits')->insert([
            'id'          => $visitId,
            'patient_id'  => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'visit_type'  => 'emergency',
            'status'      => 'open',
            'started_at'  => now(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Seed a specific real diagnosis — NOT E11.9
        \Illuminate\Support\Facades\DB::table('diagnoses')->insert([
            'id'           => (string) \Illuminate\Support\Str::uuid(),
            'patient_id'   => $patient->id,
            'visit_id'     => $visitId,
            'provider_id'  => $provider->id,
            'code_system'  => 'ICD-10',
            'code'         => 'J45.20',
            'display_name' => 'Mild intermittent asthma',
            'status'       => 'active',
            'is_primary'   => 1,
            'is_demo'      => 0,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $response = $this->postJson('/api/v1/connect/patients/emergency-profile', [
            'health_id' => $patient->health_id,
            'reason'    => 'Trauma — immediate clinical need',
        ], $this->clientHeaders());

        $response->assertStatus(200);

        $conditions = $response->json('profile.chronic_conditions');
        $this->assertNotEmpty($conditions, 'Expected real diagnosis data but got empty array');
        $codes = array_column($conditions, 'code');

        // Real seeded diagnosis must be present
        $this->assertContains('J45.20', $codes);
        // Hardcoded E11.9 must NOT be present
        $this->assertNotContains('E11.9', $codes);
    }

    // ── Task 2: RecordController system account — no bcrypt reset per push ────

    public function test_push_encounter_does_not_overwrite_system_account_password(): void
    {
        // Bypass IdempotencyProtection and RequireConsentGrant so the request
        // reaches the updateOrInsert block inside pushEncounter. VerifyIntegrationClient
        // is kept (its test-env bypass sets facility_id / provider_id attributes).
        $this->withoutMiddleware([
            \App\Http\Middleware\IdempotencyProtection::class,
            \App\Http\Middleware\RequireConsentGrant::class,
        ]);

        // Create a real facility row so updateOrInsert (the buggy path) can satisfy
        // the users.primary_facility_id FK — this ensures the update actually runs
        // and overwrites the password, giving us a true red-before-fix signal.
        $facilityId = '00000000-0000-0000-0000-000000000001';
        \DB::table('facilities')->insertOrIgnore([
            'id'         => $facilityId,
            'name'       => 'Test Facility',
            'type'       => 'hospital',
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $patient = Patient::factory()->create(['is_demo' => false]);

        // Pre-create the system account with a secure random password
        $systemId       = config('opescare.system_provider_id', '00000000-0000-0000-0000-000000000001');
        $securePassword = bcrypt(Str::random(64));
        \DB::table('users')->insertOrIgnore([
            'id'         => $systemId,
            'name'       => 'System Provider',
            'email'      => $systemId . '@system.opescare.local',
            'password'   => $securePassword,
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Call pushEncounter — response status doesn't matter for this assertion
        $this->postJson('/api/v1/connect/records/encounters', [
            'health_id'             => $patient->health_id,
            'encounter'             => ['chief_complaint' => 'Fever'],
            'external_encounter_id' => (string) Str::uuid(),
        ], $this->clientHeaders());

        // Password must NOT have been changed to bcrypt('system')
        $systemUser = \DB::table('users')->where('id', $systemId)->first();
        $this->assertFalse(
            \Hash::check('system', $systemUser->password),
            'System account password was reset to bcrypt("system") — updateOrInsert is resetting it on every push'
        );
    }

    // ── Task 3: PublicHealth — no User::first() actor fallback ───────────────

    public function test_public_health_operator_id_is_not_random_db_user(): void
    {
        // Verify the source code does not contain the User::first() fallback pattern
        $source = file_get_contents(
            base_path('app/Http/Controllers/Api/V1/PublicHealth/PublicHealthController.php')
        );
        $this->assertStringNotContainsString(
            'User::first()',
            $source,
            'PublicHealthController still contains User::first() — operator attribution is broken for B2B calls'
        );

        $source2 = file_get_contents(
            base_path('app/Http/Controllers/Api/V1/PublicHealth/IntelligenceController.php')
        );
        $this->assertStringNotContainsString(
            'User::first()',
            $source2,
            'IntelligenceController still contains User::first() — operator attribution is broken for B2B calls'
        );
    }

    // ── Task 4: StaffController roster — facility ownership ──────────────────

    /**
     * Create a real IntegrationClient with the given attributes.
     * The client_secret is stored as SHA-256(rawToken) to match VerifyIntegrationClient.
     */
    private function makeClient(array $attributes): \App\Models\IntegrationClient
    {
        return \App\Models\IntegrationClient::create(array_merge([
            'client_id'   => 'test_client_' . Str::random(8),
            'name'        => 'Test Client',
            'environment' => 'sandbox',
            'status'      => 'active',
            'scopes'      => [],
        ], $attributes));
    }

    /**
     * Return headers that authenticate as the given real IntegrationClient.
     */
    private function integrationClientHeaders(\App\Models\IntegrationClient $client, string $rawToken): array
    {
        return [
            'X-Client-ID'     => $client->client_id,
            'X-Client-Secret' => $rawToken,
        ];
    }

    public function test_get_roster_rejects_request_for_different_facility(): void
    {
        $rawToken         = Str::random(40);
        $clientFacilityId = (string) Str::uuid();
        $otherFacilityId  = (string) Str::uuid();

        $client = $this->makeClient([
            'client_secret' => hash('sha256', $rawToken),
            'facility_id'   => $clientFacilityId,
            'scopes'        => ['read_staff'],
        ]);

        // Request roster for a DIFFERENT facility — must be rejected with 403
        $response = $this->getJson(
            '/api/v1/staff/rosters?facility_id=' . $otherFacilityId,
            $this->integrationClientHeaders($client, $rawToken)
        );

        $response->assertStatus(403);
        $response->assertJsonFragment(['error' => 'ACCESS_DENIED']);
    }

    public function test_get_roster_allows_request_for_own_facility(): void
    {
        $rawToken         = Str::random(40);
        $clientFacilityId = (string) Str::uuid();

        $client = $this->makeClient([
            'client_secret' => hash('sha256', $rawToken),
            'facility_id'   => $clientFacilityId,
            'scopes'        => ['read_staff'],
        ]);

        // Request roster for OWN facility — must not be 403
        $response = $this->getJson(
            '/api/v1/staff/rosters?facility_id=' . $clientFacilityId,
            $this->integrationClientHeaders($client, $rawToken)
        );

        $this->assertNotEquals(403, $response->status());
    }

    // ── Task 5: DocumentController — no hardcoded fallbacks in official docs ──

    /** @test */
    public function document_controller_has_no_john_doe_fallback(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Api/V1/DocumentController.php'));
        $this->assertStringNotContainsString(
            "'John Doe'",
            $source,
            'DocumentController still contains hardcoded "John Doe" fallback — wrong patient name on official documents'
        );
    }

    /** @test */
    public function document_files_have_no_fake_license_number(): void
    {
        // Check controller
        $controller = file_get_contents(app_path('Http/Controllers/Api/V1/DocumentController.php'));
        $this->assertStringNotContainsString('LIC-2026-88002', $controller,
            'DocumentController still contains hardcoded fake license number');

        // Check blade views
        $bladeFiles = glob(resource_path('views/documents/*.blade.php'));
        foreach ($bladeFiles as $blade) {
            $this->assertStringNotContainsString('LIC-2026-88002', file_get_contents($blade),
                "Blade file " . basename($blade) . " still contains hardcoded fake license number");
        }
    }

    /** @test */
    public function document_controller_has_no_hardcoded_facility_name(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Api/V1/DocumentController.php'));
        $this->assertStringNotContainsString(
            "'OpesCare General Hospital'",
            $source,
            'DocumentController still contains hardcoded facility name fallback'
        );
    }
}
