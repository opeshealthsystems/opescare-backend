<?php

namespace Tests\Feature\Lifecycle;

use App\Mail\OpesCareNotificationMail;
use App\Models\OrganizationSubscription;
use App\Models\Patient;
use App\Models\SubscriptionPlan;
use Database\Seeders\PatientSubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PatientSubscriptionPlanSeeder::class);
    }

    private function premiumPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::where('slug', 'patient-premium')->firstOrFail();
    }

    private function freePlan(): SubscriptionPlan
    {
        return SubscriptionPlan::where('slug', 'patient-free')->firstOrFail();
    }

    private function makeSub(array $attributes): OrganizationSubscription
    {
        $patient = Patient::factory()->create(['email' => 'sub'.uniqid().'@example.test']);

        return OrganizationSubscription::create(array_merge([
            'subscriber_type'      => 'patient',
            'subscriber_id'        => $patient->id,
            'plan_id'              => $this->premiumPlan()->id,
            'interval'             => 'monthly',
            'status'               => 'active',
            'auto_renew'           => true,
            'current_period_start' => now()->subDays(23),
            'current_period_end'   => now()->addDays(5),
        ], $attributes));
    }

    public function test_premium_sub_ending_in_five_days_gets_a_renewal_reminder(): void
    {
        Mail::fake();
        $sub = $this->makeSub(['current_period_end' => now()->addDays(5)]);

        $this->artisan('subscriptions:lifecycle')->assertSuccessful();

        Mail::assertQueued(OpesCareNotificationMail::class, 1);
        $this->assertNotNull($sub->fresh()->renewal_reminded_at);
    }

    public function test_premium_sub_ending_in_thirty_days_gets_no_reminder(): void
    {
        Mail::fake();
        $sub = $this->makeSub(['current_period_end' => now()->addDays(30)]);

        $this->artisan('subscriptions:lifecycle')->assertSuccessful();

        Mail::assertNothingQueued();
        $this->assertNull($sub->fresh()->renewal_reminded_at);
    }

    public function test_running_twice_does_not_double_send(): void
    {
        Mail::fake();
        $this->makeSub(['current_period_end' => now()->addDays(5)]);

        $this->artisan('subscriptions:lifecycle')->assertSuccessful();
        $this->artisan('subscriptions:lifecycle')->assertSuccessful();

        // Only one message across both runs.
        Mail::assertQueued(OpesCareNotificationMail::class, 1);
    }

    public function test_recently_lapsed_sub_gets_one_winback(): void
    {
        Mail::fake();
        $sub = $this->makeSub([
            'status'               => 'expired',
            'current_period_start' => now()->subDays(33),
            'current_period_end'   => now()->subDays(3),
            'auto_renew'           => false,
        ]);

        $this->artisan('subscriptions:lifecycle')->assertSuccessful();

        Mail::assertQueued(OpesCareNotificationMail::class, 1);
        $this->assertNotNull($sub->fresh()->winback_sent_at);

        // A second run must not re-send.
        $this->artisan('subscriptions:lifecycle')->assertSuccessful();
        Mail::assertQueued(OpesCareNotificationMail::class, 1);
    }
}
