<?php
namespace Tests\Feature\PatientEngagement;

use App\Models\CarePlan;
use App\Models\CarePlanGoal;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Services\PatientEngagement\CarePlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarePlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_care_plan(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $service = new CarePlanService();
        $plan    = $service->createPlan(
            patientId:   $patient->id,
            providerId:  $provider->id,
            facilityId:  $facility->id,
            title:       'Diabetes Management Plan',
            description: 'Lifestyle and medication management for T2DM',
            startDate:   '2026-07-01',
        );

        $this->assertInstanceOf(CarePlan::class, $plan);
        $this->assertEquals('active', $plan->status);
    }

    public function test_can_add_goals_to_care_plan(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $service = new CarePlanService();
        $plan    = $service->createPlan($patient->id, $provider->id, $facility->id, 'Diabetes Plan', '', '2026-07-01');

        $goal = $service->addGoal(
            planId:     $plan->id,
            title:      'Reduce HbA1c below 7%',
            targetDate: '2026-12-31',
            category:   'clinical',
        );

        $this->assertInstanceOf(CarePlanGoal::class, $goal);
        $this->assertEquals('pending', $goal->status);
    }

    public function test_goal_can_be_marked_achieved(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $service = new CarePlanService();
        $plan    = $service->createPlan($patient->id, $provider->id, $facility->id, 'Plan', '', '2026-07-01');
        $goal    = $service->addGoal($plan->id, 'Exercise 30 min/day', '2026-08-01', 'lifestyle');

        $goal->update(['status' => 'achieved', 'achieved_at' => now()]);
        $this->assertEquals('achieved', $goal->fresh()->status);
    }
}
