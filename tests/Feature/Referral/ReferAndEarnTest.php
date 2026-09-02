<?php

namespace Tests\Feature\Referral;

use App\Models\Patient;
use App\Models\ReferralInvite;
use App\Modules\Subscription\Services\PatientSubscriptionService;
use App\Modules\Subscription\Services\ReferralRewardService;
use Database\Seeders\PatientSubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferAndEarnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PatientSubscriptionPlanSeeder::class);
    }

    private function rewards(): ReferralRewardService
    {
        return app(ReferralRewardService::class);
    }

    public function test_patient_gets_a_stable_referral_code(): void
    {
        $patient = Patient::factory()->create();

        $code1 = $this->rewards()->codeFor($patient);
        $code2 = $this->rewards()->codeFor($patient->fresh());

        $this->assertNotEmpty($code1);
        $this->assertSame(8, strlen($code1));
        $this->assertSame($code1, $code2); // stable
    }

    public function test_record_signup_rejects_self_referral(): void
    {
        $patient = Patient::factory()->create();
        $code = $this->rewards()->codeFor($patient);

        $invite = $this->rewards()->recordSignup($patient, $code);

        $this->assertNull($invite);
        $this->assertSame(0, ReferralInvite::count());
    }

    public function test_valid_referral_rewards_both_patients_with_premium(): void
    {
        $referrer = Patient::factory()->create();
        $referee  = Patient::factory()->create();
        $code = $this->rewards()->codeFor($referrer);

        $invite = $this->rewards()->recordSignup($referee, $code);
        $this->assertNotNull($invite);
        $this->assertSame('joined', $invite->status);

        $this->rewards()->grantRewards($invite);

        $invite->refresh();
        $this->assertSame('rewarded', $invite->status);
        $this->assertNotNull($invite->rewarded_at);

        $subs = app(PatientSubscriptionService::class);
        // Both parties now have a Premium feature (e.g. teleconsult is Premium-only).
        $this->assertTrue($subs->hasFeature($referrer->fresh(), 'teleconsult'));
        $this->assertTrue($subs->hasFeature($referee->fresh(), 'teleconsult'));
    }

    public function test_duplicate_referee_is_rejected(): void
    {
        $referrerA = Patient::factory()->create();
        $referrerB = Patient::factory()->create();
        $referee   = Patient::factory()->create();

        $codeA = $this->rewards()->codeFor($referrerA);
        $codeB = $this->rewards()->codeFor($referrerB);

        $this->assertNotNull($this->rewards()->recordSignup($referee, $codeA));
        $this->assertNull($this->rewards()->recordSignup($referee, $codeB));
        $this->assertSame(1, ReferralInvite::count());
    }

    public function test_registration_still_succeeds_with_garbage_referral_code(): void
    {
        \App\Models\Role::firstOrCreate(['name' => 'patient']);

        // Sign-up is two steps now. The referral code is captured here and only
        // redeemed at profile completion, once a Patient actually exists — so a
        // garbage code has to survive the hand-off without breaking either step.
        $this->post(route('register.patient.submit'), [
            'email'                 => 'ada.referral@example.test',
            'password'              => 'password1234',
            'password_confirmation' => 'password1234',
            'ref'                   => 'TOTALLY-INVALID-CODE!!',
        ])->assertRedirect(route('portals.patient.complete-profile'));

        $this->post(route('portals.patient.complete-profile.store'), [
            'first_name' => 'Ada',
            'last_name'  => 'Lovelace',
            'dob'        => '1990-12-10',
            'sex'        => 'female',
            'phone'      => '+237600000001',
        ])->assertRedirect(route('portals.patient'));

        $this->assertDatabaseHas('patients', ['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        // No invite should have been created from a garbage code.
        $this->assertSame(0, ReferralInvite::count());
    }
}
