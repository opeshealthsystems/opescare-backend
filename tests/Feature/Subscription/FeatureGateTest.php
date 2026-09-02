<?php

namespace Tests\Feature\Subscription;

use App\Models\Patient;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Modules\Subscription\Services\PatientSubscriptionService;
use Database\Seeders\PatientSubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FeatureGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PatientSubscriptionPlanSeeder::class);

        // Throwaway route guarded by the feature gate.
        Route::middleware(['web', 'patient.feature:teleconsult'])
            ->get('/__test/premium', fn () => response('ok'));
    }

    private function userForNewPatient(): array
    {
        $patient = Patient::factory()->create();
        $user    = User::factory()->create(['patient_id' => $patient->id]);
        return [$patient, $user];
    }

    public function test_free_patient_is_redirected_to_subscription_page(): void
    {
        [$patient, $user] = $this->userForNewPatient();
        app(PatientSubscriptionService::class)->ensureFreePlan($patient);

        $this->actingAs($user)
            ->get('/__test/premium')
            ->assertRedirect(route('portals.patient.subscription'));
    }

    public function test_free_patient_json_request_gets_403(): void
    {
        [$patient, $user] = $this->userForNewPatient();
        app(PatientSubscriptionService::class)->ensureFreePlan($patient);

        $this->actingAs($user)
            ->getJson('/__test/premium')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'FEATURE_NOT_ENTITLED');
    }

    public function test_premium_patient_passes_the_gate(): void
    {
        [$patient, $user] = $this->userForNewPatient();
        $premium = SubscriptionPlan::where('slug', 'patient-premium')->firstOrFail();
        app(PatientSubscriptionService::class)->startSubscription($patient, $premium, 'monthly');

        $this->actingAs($user)
            ->get('/__test/premium')
            ->assertOk()
            ->assertSee('ok');
    }
}
