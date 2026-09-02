<?php

namespace App\Services\Clinical;

use App\Models\CarePlan;
use App\Models\CarePlanGoal;
use App\Models\CarePlanIntervention;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CarePlanService
{
    public function create(array $data): CarePlan
    {
        return CarePlan::create(array_merge(['status' => 'active'], $data));
    }

    public function addGoal(string $carePlanId, array $data): CarePlanGoal
    {
        return CarePlanGoal::create(array_merge(['status' => 'pending'], $data, ['care_plan_id' => $carePlanId]));
    }

    public function updateGoalStatus(string $goalId, string $status): CarePlanGoal
    {
        $goal = CarePlanGoal::findOrFail($goalId);
        $goal->status = $status;

        if ($status === 'achieved') {
            $goal->achieved_at = Carbon::now();
        }

        $goal->save();
        return $goal;
    }

    public function addIntervention(string $carePlanId, array $data): CarePlanIntervention
    {
        return CarePlanIntervention::create(array_merge($data, ['care_plan_id' => $carePlanId]));
    }

    public function getActivePlansForPatient(string $patientId): Collection
    {
        return CarePlan::where('patient_id', $patientId)
            ->where('status', 'active')
            ->with(['goals', 'interventions'])
            ->latest()
            ->get();
    }

    public function getSummary(string $carePlanId): array
    {
        $plan = CarePlan::with(['goals', 'interventions'])->findOrFail($carePlanId);

        $totalGoals    = $plan->goals->count();
        $achievedGoals = $plan->goals->where('status', 'achieved')->count();
        $progressPct   = $totalGoals > 0
            ? (int) round(($achievedGoals / $totalGoals) * 100)
            : 0;

        return [
            'plan'          => $plan,
            'goals'         => $plan->goals,
            'interventions' => $plan->interventions,
            'progress_pct'  => $progressPct,
        ];
    }
}
