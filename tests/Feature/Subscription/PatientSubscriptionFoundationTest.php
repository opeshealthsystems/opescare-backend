<?php

namespace Tests\Feature\Subscription;

use App\Models\OrganizationSubscription;
use App\Models\Patient;
use App\Models\SubscriptionPlan;
use Database\Seeders\PatientSubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientSubscriptionFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_patient_free_and_premium_plans(): void
    {
        $this->seed(PatientSubscriptionPlanSeeder::class);

        $plans = SubscriptionPlan::forAudience('patient')->active()->public()->get();
        $this->assertCount(2, $plans);

        $free = SubscriptionPlan::where('slug', 'patient-free')->firstOrFail();
        $this->assertTrue($free->isFree());
        $this->assertSame('patient', $free->audience);

        $premium = SubscriptionPlan::where('slug', 'patient-premium')->firstOrFail();
        $this->assertFalse($premium->isFree());
        $this->assertSame(150000, $premium->price_kobo);
        $this->assertSame(1500000, $premium->priceForInterval('annual'));
        $this->assertSame(150000, $premium->priceForInterval('monthly'));
        $this->assertTrue($premium->hasFeature('teleconsult'));
        $this->assertTrue($premium->hasFeature('family_sharing'));
    }

    public function test_a_patient_can_be_subscribed_to_premium_via_polymorphic_subscriber(): void
    {
        $this->seed(PatientSubscriptionPlanSeeder::class);
        $premium = SubscriptionPlan::where('slug', 'patient-premium')->firstOrFail();
        $patient = Patient::factory()->create();

        $sub = OrganizationSubscription::create([
            'subscriber_type'      => 'patient',
            'subscriber_id'        => $patient->id,
            'interval'             => 'monthly',
            'plan_id'              => $premium->id,
            'status'               => 'active',
            'current_period_start' => now()->toDateString(),
            'current_period_end'   => now()->addMonth()->toDateString(),
            'auto_renew'           => true,
        ]);

        $this->assertTrue($sub->isB2C());
        $this->assertTrue($sub->isActive());
        $this->assertInstanceOf(Patient::class, $sub->subscriber);
        $this->assertSame($patient->id, $sub->subscriber->id);
        $this->assertNull($sub->organization_id);
    }

    public function test_existing_facility_plans_remain_tagged_facility(): void
    {
        // A plain plan created without an audience defaults to facility (B2B intact).
        $plan = SubscriptionPlan::create([
            'name'        => 'Starter',
            'slug'        => 'facility-starter-test',
            'billing_cycle' => 'monthly',
            'price_kobo'  => 2500000,
            'currency'    => 'XAF',
            'is_active'   => true,
            'is_public'   => true,
        ]);

        $this->assertSame('facility', $plan->fresh()->audience);
    }
}
