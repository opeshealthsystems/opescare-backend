<?php

namespace Tests\Feature\MedicalId;

use Tests\TestCase;
use App\Services\Identity\QrTokenService;
use App\Models\Patient;
use App\Models\HealthIdQrToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class QrTokenSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected QrTokenService $qrService;
    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qrService = new QrTokenService();
        config(['demo.enabled' => false]);

        $this->patient = Patient::forceCreate([
            'health_id' => 'CM-HID-7KQ9-MP42-X8D1',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'phone_number' => '1234567890',
            'is_demo' => false,
        ]);
    }

    public function test_qr_payload_does_not_contain_clinical_data_and_is_stored_hashed()
    {
        $result   = $this->qrService->generateToken($this->patient->id);
        $rawToken = $result['raw_token'];
        $model    = $result['model'];

        // Token must not embed patient identity data
        $this->assertStringNotContainsString($this->patient->health_id, $rawToken);
        $this->assertStringNotContainsString('John', $rawToken);

        // Token format: qrx_{lookupId}_{secret}
        $this->assertStringStartsWith('qrx_', $rawToken);
        $parts = explode('_', $rawToken, 3);
        $this->assertCount(3, $parts);
        $secret = $parts[2];

        // DB stores lookup_id plaintext and hashes only the secret half
        $this->assertNotNull($model->lookup_id);
        $this->assertNotEquals($rawToken, $model->token_hash);
        // Only the secret portion hashes to token_hash — not the full token
        $this->assertTrue(Hash::check($secret, $model->token_hash));
    }

    public function test_qr_token_can_be_revoked()
    {
        $result = $this->qrService->generateToken($this->patient->id);
        $rawToken = $result['raw_token'];
        $model = $result['model'];

        $verified = $this->qrService->verifyToken($rawToken);
        $this->assertNotNull($verified);

        $this->qrService->revokeToken($model);

        $verifiedAfterRevoke = $this->qrService->verifyToken($rawToken);
        $this->assertNull($verifiedAfterRevoke);
    }

    public function test_expired_qr_token_fails()
    {
        $result = $this->qrService->generateToken($this->patient->id, 'temporary_consent_qr', -5); // Expired 5 mins ago
        $rawToken = $result['raw_token'];

        $verified = $this->qrService->verifyToken($rawToken);
        $this->assertNull($verified);
    }
}
