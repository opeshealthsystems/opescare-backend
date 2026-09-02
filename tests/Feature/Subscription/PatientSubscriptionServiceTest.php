<?php

namespace Tests\Feature\Subscription;

use App\Models\Patient;
use App\Models\SubscriptionPlan;
use App\Modules\Subscription\Services\PatientSubscriptionService;
use Database\Seeders\PatientSubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientSubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PatientSubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PatientSubscriptionPlanSeeder::class);
        $this->service = app(PatientSubscriptionService::class);
    }

    public function test_ensure_free_plan_is_idempotent_and_grants_free_features(): void
    {
        $patient = Patient::factory()->create();

        $a = $this->service->ensureFreePlan($patient);
        $b = $this->service->ensureFreePlan($patient);

        $this->assertSame($a->id, $b->id, 'ensureFreePlan must be idempotent');
        $this->assertSame('patient-free', $a->plan->slug);
        $this->assertTrue($this->service->hasFeature($patient, 'basic_booking'));
        $this->assertFalse($this->service->hasFeature($patient, 'teleconsult'));
    }

    public function test_starting_premium_unlocks_premium_features_and_closes_free(): void
    {
        $patient = Patient::factory()->create();
        $this->service->ensureFreePlan($patient);

        $premium = SubscriptionPlan::where('slug', 'patient-premium')->firstOrFail();
        $sub = $this->service->startSubscription($patient, $premium, 'annual');

        $this->assertSame('active', $sub->status);
        $this->assertSame('annual', $sub->interval);
        $this->assertTrue($sub->auto_renew);
        // period is one year out for annual
        $this->assertSame(
            now()->addYear()->toDateString(),
            $sub->current_period_end->toDateString()
        );
        // Premium feature now available; exactly one active subscription remains.
        $this->assertTrue($this->service->hasFeature($patient, 'teleconsult'));
        $this->assertSame(5, $this->service->featureLimit($patient, 'family_sharing'));
        $this->assertSame($premium->id, $this->service->activeSubscription($patient)->plan_id);
    }

    public function test_cancel_keeps_active_until_period_end_but_stops_autorenew(): void
    {
        $patient = Patient::factory()->create();
        $premium = SubscriptionPlan::where('slug', 'patient-premium')->firstOrFail();
        $this->service->startSubscription($patient, $premium, 'monthly');

        $cancelled = $this->service->cancel($patient, 'too expensive');

        $this->assertFalse($cancelled->auto_renew);
        $this->assertSame('active', $cancelled->status, 'access runs to period end');
        $this->assertTrue($this->service->hasFeature($patient, 'teleconsult'));
    }
}
