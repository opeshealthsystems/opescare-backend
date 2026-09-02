<?php

namespace Tests\Feature\Payments;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentsSmokeTest extends TestCase
{
    public function test_reports_failure_when_momo_token_rejected(): void
    {
        config([
            'services.mtn_momo.subscription_key' => 'k',
            'services.mtn_momo.api_key' => 'x',
            'services.mtn_momo.user_id' => 'u',
            'services.orange_money.client_id' => '',
        ]);
        Http::fake(['*/collection/token/' => Http::response([], 401)]);

        $this->artisan('payments:smoke')->assertExitCode(1);
    }

    public function test_skips_unconfigured_providers(): void
    {
        config([
            'services.mtn_momo.subscription_key' => '',
            'services.orange_money.client_id' => '',
        ]);

        $this->artisan('payments:smoke')->assertExitCode(0);
    }

    public function test_passes_when_momo_token_obtained(): void
    {
        config([
            'services.mtn_momo.subscription_key' => 'k',
            'services.mtn_momo.api_key' => 'x',
            'services.mtn_momo.user_id' => 'u',
            'services.orange_money.client_id' => '',
        ]);
        Http::fake(['*/collection/token/' => Http::response(['access_token' => 'tok'], 200)]);

        $this->artisan('payments:smoke')->assertExitCode(0);
    }
}
