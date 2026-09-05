<?php

namespace Tests\Feature\Portal;

use App\Models\Patient;
use App\Services\Identity\QrTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /verify/qr/{token} — the page a provider lands on after scanning the
 * live code from the patient app. One answer per token state, a recorded
 * verification on success, and never the bearer secret back on the screen.
 */
class QrVerifyPageTest extends TestCase
{
    use RefreshDatabase;

    private function patient(): Patient
    {
        return Patient::factory()->create([
            'health_id'   => 'CM-HID-TEST-QR01-0001',
            'first_name'  => 'Amara',
            'last_name'   => 'Ndi',
            'sex'         => 'female',
            'blood_group' => 'O+',
            'is_demo'     => (bool) config('demo.enabled'),
        ]);
    }

    public function test_a_live_code_verifies_and_is_recorded(): void
    {
        $patient = $this->patient();
        $issued  = app(QrTokenService::class)->generateToken($patient->id, 'temporary_access_qr', 15);

        $response = $this->get('/verify/qr/' . $issued['raw_token']);

        $response->assertOk()
            ->assertSee(__('verify.result_verified'))
            ->assertSee('CM-HID-TEST-QR01-0001')
            ->assertSee('Amara Ndi')
            ->assertSee(__('verify.sex_female'))
            ->assertSee(__('verify.meta_reference'))
            // The raw token is the bearer secret; it must not be echoed back.
            ->assertDontSee($issued['raw_token']);

        $this->assertDatabaseHas('health_id_verification_events', [
            'patient_id'          => $patient->id,
            'verification_type'   => 'qr_scan',
            'verification_status' => 'verified',
            'evidence_reference'  => $issued['model']->uuid,
        ]);
    }

    public function test_an_expired_code_says_expired_and_discloses_nothing(): void
    {
        $patient = $this->patient();
        $issued  = app(QrTokenService::class)->generateToken($patient->id, 'temporary_access_qr', 15);
        $issued['model']->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->get('/verify/qr/' . $issued['raw_token'])
            ->assertOk()
            ->assertSee(__('verify.qr_expired_title'))
            ->assertDontSee('Amara Ndi')
            ->assertDontSee('CM-HID-TEST-QR01-0001');

        $this->assertDatabaseCount('health_id_verification_events', 0);
    }

    public function test_a_withdrawn_code_says_withdrawn(): void
    {
        $patient = $this->patient();
        $issued  = app(QrTokenService::class)->generateToken($patient->id, 'temporary_access_qr', 15);
        $issued['model']->forceFill(['status' => 'revoked', 'revoked_at' => now()])->save();

        $this->get('/verify/qr/' . $issued['raw_token'])
            ->assertOk()
            ->assertSee(__('verify.qr_revoked_title'))
            ->assertDontSee('Amara Ndi');
    }

    public function test_a_wrong_secret_on_a_live_code_is_invalid(): void
    {
        $patient = $this->patient();
        $issued  = app(QrTokenService::class)->generateToken($patient->id, 'temporary_access_qr', 15);
        [$prefix, $lookup] = explode('_', $issued['raw_token'], 3);

        $this->get("/verify/qr/{$prefix}_{$lookup}_not-the-secret")
            ->assertOk()
            ->assertSee(__('verify.qr_invalid_title'))
            ->assertDontSee('Amara Ndi');
    }

    public function test_garbage_is_invalid(): void
    {
        $this->get('/verify/qr/not-a-token')
            ->assertOk()
            ->assertSee(__('verify.qr_invalid_title'));
    }
}
