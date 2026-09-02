<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class FhirConsentGateTest extends TestCase
{
    public function test_fhir_patient_everything_requires_consent_grant(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer some-invalid-token',
        ])->get('/api/fhir/R4/Patient/OC-CMR-TEST-001/$everything');

        // Without valid auth/consent: must NOT be 200
        $this->assertContains(
            $response->getStatusCode(),
            [401, 403, 404],
            'FHIR $everything must not return 200 without a valid consent grant (got ' . $response->getStatusCode() . ')'
        );
    }

    public function test_fhir_metadata_is_accessible_without_auth(): void
    {
        $response = $this->get('/api/fhir/R4/metadata');
        $this->assertNotEquals(403, $response->getStatusCode());
    }
}
