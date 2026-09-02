<?php

namespace Tests\Feature;

use App\Models\CarePlan;
use App\Models\CarePlanGoal;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Services\Clinical\CarePlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarePlanTest extends TestCase
{
    use RefreshDatabase;

    private CarePlanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CarePlanService::class);
    }

    public function test_can_create_care_plan(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();
        $user     = User::factory()->create();

        $plan = $this->service->create([
            'patient_id'  => $patient->id,
            'facility_id' => $facility->id,
            'created_by'  => $user->id,
            'title'       => 'Hypertension Management Plan',
            'start_date'  => now()->toDateString(),
        ]);

        $this->assertInstanceOf(CarePlan::class, $plan);
        $this->assertEquals('active', $plan->status);
    }

    public function test_can_add_goal_and_update_status(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();
        $user     = User::factory()->create();
        $plan     = $this->service->create([
            'patient_id'  => $patient->id,
            'facility_id' => $facility->id,
            'created_by'  => $user->id,
            'title'       => 'Test Plan',
            'start_date'  => now()->toDateString(),
        ]);

        $goal = $this->service->addGoal($plan->id, [
            'goal_text'   => 'Reduce systolic BP below 130 mmHg',
            'target_date' => now()->addMonths(3)->toDateString(),
        ]);

        $this->assertEquals('pending', $goal->status);

        $updated = $this->service->updateGoalStatus($goal->id, 'achieved');
        $this->assertEquals('achieved', $updated->status);
        $this->assertNotNull($updated->achieved_at);
    }

    public function test_progress_pct_calculated_correctly(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();
        $user     = User::factory()->create();
        $plan     = $this->service->create([
            'patient_id'  => $patient->id,
            'facility_id' => $facility->id,
            'created_by'  => $user->id,
            'title'       => 'Progress Test Plan',
            'start_date'  => now()->toDateString(),
        ]);

        $goal1 = $this->service->addGoal($plan->id, ['goal_text' => 'Goal 1']);
        $goal2 = $this->service->addGoal($plan->id, ['goal_text' => 'Goal 2']);
        $goal3 = $this->service->addGoal($plan->id, ['goal_text' => 'Goal 3']);

        // Achieve 2 of 3
        $this->service->updateGoalStatus($goal1->id, 'achieved');
        $this->service->updateGoalStatus($goal2->id, 'achieved');

        $summary = $this->service->getSummary($plan->id);
        $this->assertEquals(67, $summary['progress_pct']);
    }
}
