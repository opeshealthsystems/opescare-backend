<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class UssdCallbackSecurityTest extends TestCase
{
    public function test_ussd_callback_requires_shared_secret_when_configured(): void
    {
        config(['services.africastalking.ussd_callback_secret' => 'secret']);

        $this->post('/api/ussd/callback', [
            'sessionId' => 's1',
            'serviceCode' => '*123#',
            'phoneNumber' => '+237600000000',
            'text' => '',
        ])->assertStatus(401);
    }
}
