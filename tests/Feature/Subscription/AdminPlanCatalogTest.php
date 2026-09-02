<?php

namespace Tests\Feature\Subscription;

use App\Modules\Subscription\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPlanCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_plan_persists_audience_and_annual_price(): void
    {
        $svc = app(SubscriptionService::class);

        $plan = $svc->createPlan([
            'name'          => 'Santé+ Test',
            'audience'      => 'patient',
            'billing_cycle' => 'monthly',
            'price'         => 1500,
            'annual_price'  => 15000,
            'currency'      => 'XAF',
        ], 'tester');

        $this->assertSame('patient', $plan->audience);
        $this->assertSame(150000, $plan->price_kobo);
        $this->assertSame(1500000, $plan->annual_price_kobo);
        $this->assertSame('XAF 15,000', $plan->annualPriceFormatted());
    }

    public function test_create_plan_defaults_to_facility_and_no_annual(): void
    {
        $svc = app(SubscriptionService::class);

        $plan = $svc->createPlan([
            'name'          => 'Starter Test',
            'billing_cycle' => 'monthly',
            'price'         => 250,
        ], 'tester');

        $this->assertSame('facility', $plan->audience);
        $this->assertNull($plan->annual_price_kobo);
        $this->assertNull($plan->annualPriceFormatted());
    }
}
