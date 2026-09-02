<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Services\TwoFactorService;
use Tests\TestCase;

/**
 * Correctness + behaviour tests for the TOTP scaffold (GAP-009, MFA half).
 * Pinned to the RFC 6238 reference vector so the pure-PHP implementation is
 * provably standards-compliant before it backs real authentication.
 */
class TwoFactorServiceTest extends TestCase
{
    private function service(): TwoFactorService
    {
        config()->set('mfa.totp', ['digits' => 6, 'period' => 30, 'window' => 1, 'algorithm' => 'sha1']);
        return new TwoFactorService();
    }

    public function test_matches_rfc6238_reference_vector(): void
    {
        // RFC 6238 Appendix B: ASCII seed "12345678901234567890" → Base32, SHA1.
        // At Unix time 59 the 8-digit TOTP is 94287082, so the 6-digit code is 287082.
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        $this->assertSame('287082', $this->service()->codeAt($secret, 59));
    }

    public function test_verify_accepts_current_code(): void
    {
        $svc    = $this->service();
        $secret = $svc->generateSecret();
        $now    = 1_700_000_000;

        $this->assertTrue($svc->verify($secret, $svc->codeAt($secret, $now), $now));
    }

    public function test_verify_allows_one_period_of_drift(): void
    {
        $svc    = $this->service();
        $secret = $svc->generateSecret();
        $now    = 1_700_000_000;

        // Code from the previous 30s window still verifies (window = 1).
        $prev = $svc->codeAt($secret, $now - 30);
        $this->assertTrue($svc->verify($secret, $prev, $now));

        // Two windows out is rejected.
        $tooOld = $svc->codeAt($secret, $now - 90);
        $this->assertFalse($svc->verify($secret, $tooOld, $now));
    }

    public function test_verify_rejects_garbage(): void
    {
        $svc    = $this->service();
        $secret = $svc->generateSecret();

        $this->assertFalse($svc->verify($secret, '000000'));
        $this->assertFalse($svc->verify($secret, 'abcdef'));
        $this->assertFalse($svc->verify($secret, ''));
    }

    public function test_provisioning_uri_is_well_formed(): void
    {
        $uri = $this->service()->provisioningUri('GEZDGNBVGY3TQOJQ', 'doctor@opescare.com', 'OpesCare');

        $this->assertStringStartsWith('otpauth://totp/OpesCare:', $uri);
        $this->assertStringContainsString('secret=GEZDGNBVGY3TQOJQ', $uri);
        $this->assertStringContainsString('issuer=OpesCare', $uri);
    }

    public function test_recovery_codes_are_unique_and_formatted(): void
    {
        $codes = $this->service()->generateRecoveryCodes(8);

        $this->assertCount(8, $codes);
        $this->assertCount(8, array_unique($codes));
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code);
        }
    }
}
