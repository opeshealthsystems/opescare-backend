<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProviderMobileAuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_login_rejects_invalid_pin_hash(): void
    {
        User::factory()->create([
            'email' => 'clinician@example.test',
            'password' => Hash::make('correct-password'),
            'status' => 'active',
        ]);

        $this->postJson('/api/provider-mobile/auth/login', [
            'email' => 'clinician@example.test',
            'pin_hash' => 'wrong-password',
            'device_fingerprint' => 'device-1',
            'platform' => 'android',
        ])->assertStatus(401);
    }

    public function test_provider_otp_verify_rejects_without_pending_challenge(): void
    {
        $this->postJson('/api/provider-mobile/auth/otp/verify', [
            'otp_code' => '123456',
            'device_fingerprint' => 'device-1',
            'platform' => 'android',
        ])->assertStatus(401);
    }
}
