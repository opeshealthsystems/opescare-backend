<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class MobileMoneyCallbackSecurityTest extends TestCase
{
    public function test_mtn_callback_requires_valid_signature_when_secret_configured(): void
    {
        config(['services.mtn_momo.callback_secret' => 'test-secret']);

        $this->postJson('/api/payments/mobile-money/mtn/callback', [
            'referenceId' => 'pay_123',
            'status' => 'SUCCESSFUL',
        ])->assertStatus(401);
    }

    public function test_orange_callback_requires_valid_signature_when_secret_configured(): void
    {
        config(['services.orange_money.callback_secret' => 'test-secret']);

        $this->postJson('/api/payments/mobile-money/orange/callback', [
            'txnid' => 'pay_123',
            'status' => 'SUCCESS',
        ])->assertStatus(401);
    }
}
