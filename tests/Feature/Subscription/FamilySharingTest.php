<?php

namespace Tests\Feature\Subscription;

use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Modules\Subscription\Services\PatientSubscriptionService;
use Database\Seeders\PatientSubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilySharingTest extends TestCase
{
    use RefreshDatabase;

    private PatientSubscriptionService $service;
    private SubscriptionPlan $premium;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PatientSubscriptionPlanSeeder::class);
        $this->service = app(PatientSubscriptionService::class);
        $this->premium = SubscriptionPlan::where('slug', 'patient-premium')->firstOrFail();
    }

    private function guardianOnPremium(): array
    {
        $patient = Patient::factory()->create();
        $user    = User::factory()->create(['patient_id' => $patient->id]);
        $this->service->startSubscription($patient, $this->premium, 'monthly');
        return [$patient, $user];
    }

    private function link(User $guardian, Patient $dependent, int $order = 0): void
    {
        FamilyLink::create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $dependent->id,
            'relationship'         => 'child',
            'status'               => 'active',
            'created_by'           => 'test',
            'created_at'           => now()->addSeconds($order),
            'updated_at'           => now()->addSeconds($order),
        ]);
    }

    public function test_dependent_inherits_premium_feature_from_guardian(): void
    {
        [, $guardianUser] = $this->guardianOnPremium();
        $dependent = Patient::factory()->create();
        $this->link($guardianUser, $dependent);

        $this->assertTrue($this->service->hasFeature($dependent, 'teleconsult'));

        // An unlinked patient gets no inherited coverage.
        $stranger = Patient::factory()->create();
        $this->assertFalse($this->service->hasFeature($stranger, 'teleconsult'));
    }

    public function test_family_sharing_respects_the_seat_limit(): void
    {
        [$guardianPatient, $guardianUser] = $this->guardianOnPremium();

        // 6 dependents, plan grants 5 seats.
        $deps = [];
        for ($i = 0; $i < 6; $i++) {
            $deps[$i] = Patient::factory()->create();
            $this->link($guardianUser, $deps[$i], $i);
        }

        $seats = $this->service->familySeats($guardianPatient);
        $this->assertSame(5, $seats['total']);
        $this->assertSame(5, $seats['used']);

        // Earliest-linked 5 are covered; the 6th is not.
        $this->assertTrue($this->service->hasFeature($deps[0], 'teleconsult'));
        $this->assertTrue($this->service->hasFeature($deps[4], 'teleconsult'));
        $this->assertFalse($this->service->hasFeature($deps[5], 'teleconsult'));
    }

    public function test_free_guardian_does_not_cover_dependents(): void
    {
        $guardianPatient = Patient::factory()->create();
        $guardianUser    = User::factory()->create(['patient_id' => $guardianPatient->id]);
        $this->service->ensureFreePlan($guardianPatient);

        $dependent = Patient::factory()->create();
        $this->link($guardianUser, $dependent);

        $this->assertFalse($this->service->hasFeature($dependent, 'teleconsult'));
        $this->assertSame(0, $this->service->familySeats($guardianPatient)['total']);
    }
}
