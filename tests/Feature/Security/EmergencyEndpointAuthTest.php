<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class EmergencyEndpointAuthTest extends TestCase
{
    public function test_emergency_profile_endpoint_requires_authentication(): void
    {
        // Hit the endpoint without any auth token
        $response = $this->postJson('/api/v1/connect/patients/emergency-profile', [
            'health_id' => 'OC-CMR-TEST-001',
            'reason'    => 'Emergency test',
        ]);

        // Must NOT be 200 — should be 401 or 403
        $this->assertContains(
            $response->getStatusCode(),
            [401, 403],
            'Emergency profile endpoint must require authentication (got ' . $response->getStatusCode() . ')'
        );
    }

    public function test_emergency_audit_does_not_use_random_uuid_as_client_id(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/Api/V1/Connect/EmergencyAccessController.php')
        );

        $this->assertStringNotContainsString(
            'Str::uuid()',
            $source,
            'EmergencyAccessController must not use Str::uuid() as client ID in audit — use real authenticated client ID'
        );
    }
}
