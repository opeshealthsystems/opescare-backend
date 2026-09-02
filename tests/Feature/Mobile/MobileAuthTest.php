<?php

namespace Tests\Feature\Mobile;

use Tests\TestCase;
use App\Models\Patient;
use App\Models\PatientOtpCode;
use App\Models\PatientAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->patient = Patient::forceCreate([
            'health_id'    => 'CM-HID-AUTH-TEST1',
            'first_name'   => 'Jane',
            'last_name'    => 'Patient',
            'date_of_birth' => '1992-06-15',
            'sex'          => 'female',
            'phone_number' => '0700000001',
            'pin_hash'     => Hash::make('1234'),
            'is_demo'      => false,
        ]);
    }

    public function test_login_with_valid_pin_sends_otp(): void
    {
        $response = $this->postJson('/api/mobile/auth/login', [
            'phone_number' => '0700000001',
            'pin'          => '1234',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'OTP sent to your registered phone number.']);

        $this->assertDatabaseHas('patient_otp_codes', [
            'phone_number' => '0700000001',
        ]);
    }

    public function test_login_with_wrong_pin_returns_401(): void
    {
        $response = $this->postJson('/api/mobile/auth/login', [
            'phone_number' => '0700000001',
            'pin'          => '9999',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_for_unknown_phone_returns_404(): void
    {
        $response = $this->postJson('/api/mobile/auth/login', [
            'phone_number' => '0000000000',
            'pin'          => '1234',
        ]);

        $response->assertStatus(404);
    }

    public function test_verify_otp_issues_access_token(): void
    {
        // Pre-create a valid OTP
        $otp = '123456';
        PatientOtpCode::create([
            'phone_number' => '0700000001',
            'code_hash'    => Hash::make($otp),
            'expires_at'   => Carbon::now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/mobile/auth/otp/verify', [
            'phone_number' => '0700000001',
            'otp'          => $otp,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'patient_id'])
                 ->assertJsonFragment(['status' => 'authenticated', 'token_type' => 'Bearer']);

        $this->assertDatabaseHas('patient_access_tokens', [
            'patient_id' => $this->patient->id,
        ]);
    }

    public function test_verify_with_wrong_otp_returns_401(): void
    {
        PatientOtpCode::create([
            'phone_number' => '0700000001',
            'code_hash'    => Hash::make('654321'),
            'expires_at'   => Carbon::now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/mobile/auth/otp/verify', [
            'phone_number' => '0700000001',
            'otp'          => '000000',
        ]);

        $response->assertStatus(401);
    }

    public function test_verify_with_expired_otp_returns_401(): void
    {
        PatientOtpCode::create([
            'phone_number' => '0700000001',
            'code_hash'    => Hash::make('123456'),
            'expires_at'   => Carbon::now()->subMinutes(1), // already expired
        ]);

        $response = $this->postJson('/api/mobile/auth/otp/verify', [
            'phone_number' => '0700000001',
            'otp'          => '123456',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_mobile_route_requires_token(): void
    {
        // Try to list facilities without a token
        $response = $this->getJson('/api/mobile/facilities');
        $response->assertStatus(401);
    }

    public function test_authenticated_mobile_route_works_with_valid_token(): void
    {
        // Create a valid access token (with token_prefix for O(1) lookup)
        $rawToken = 'pat_' . str_repeat('a', 40);
        PatientAccessToken::create([
            'patient_id'   => $this->patient->id,
            'token_hash'   => Hash::make($rawToken),
            'token_prefix' => substr($rawToken, 0, 12),
            'expires_at'   => Carbon::now()->addHours(24),
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$rawToken}"])
                         ->getJson('/api/mobile/facilities');

        // Should not be 401 (may be 200 or another code depending on facility data)
        $this->assertNotEquals(401, $response->status());
    }

    public function test_otp_cannot_be_reused(): void
    {
        $otp = '112233';
        PatientOtpCode::create([
            'phone_number' => '0700000001',
            'code_hash'    => Hash::make($otp),
            'expires_at'   => Carbon::now()->addMinutes(10),
        ]);

        // First use — should succeed
        $this->postJson('/api/mobile/auth/otp/verify', [
            'phone_number' => '0700000001',
            'otp'          => $otp,
        ])->assertStatus(200);

        // Second use — should fail (used_at is now set)
        $this->postJson('/api/mobile/auth/otp/verify', [
            'phone_number' => '0700000001',
            'otp'          => $otp,
        ])->assertStatus(401);
    }

    public function test_pin_bootstrap_requires_date_of_birth(): void
    {
        // Patient with no PIN set
        $patient = Patient::forceCreate([
            'health_id'     => 'CM-HID-NPIN-0001',
            'first_name'    => 'New',
            'last_name'     => 'Patient',
            'date_of_birth' => '1985-03-20',
            'sex'           => 'male',
            'phone_number'  => '0700000099',
            'pin_hash'      => null,
            'is_demo'       => false,
        ]);

        // Without date_of_birth — should be rejected
        $this->postJson('/api/mobile/auth/login', [
            'phone_number' => '0700000099',
            'pin'          => '5678',
        ])->assertStatus(422);

        // Wrong date_of_birth — should be rejected
        $this->postJson('/api/mobile/auth/login', [
            'phone_number'  => '0700000099',
            'pin'           => '5678',
            'date_of_birth' => '2000-01-01',
        ])->assertStatus(422);

        // Correct date_of_birth — PIN bootstrap should succeed and OTP sent
        $this->postJson('/api/mobile/auth/login', [
            'phone_number'  => '0700000099',
            'pin'           => '5678',
            'date_of_birth' => '1985-03-20',
        ])->assertStatus(200);

        // Verify PIN was persisted
        $patient->refresh();
        $this->assertTrue(Hash::check('5678', $patient->pin_hash));
    }
}
