<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Clinical\CarePlanService;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarePlanController extends Controller
{
    public function __construct(
        private readonly CarePlanService       $service,
        private readonly DocumentIssuanceService $issuance
    ) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'patient_id'  => 'required|uuid|exists:patients,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'status'      => 'nullable|in:active,completed,on_hold,cancelled',
            'visit_id'    => 'nullable|uuid',
        ]);

        $validated['facility_id'] = $facilityId;
        $validated['created_by'] = $request->user()->id;

        $plan = $this->service->create($validated);

        try {
            $planId = is_array($plan) ? ($plan['id'] ?? null) : ($plan->id ?? null);
            $this->issuance->issueFromModel(
                'CPL',
                'Care Plan — ' . $validated['title'],
                ['plan_id' => $planId, 'patient_id' => $validated['patient_id'], 'title' => $validated['title'], 'start_date' => $validated['start_date'], 'end_date' => $validated['end_date'] ?? null, 'status' => $validated['status'] ?? 'active'],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['created_by'] ?? null
            );
        } catch (\Throwable) {}

        return response()->json(['data' => $plan], 201);
    }

    public function show(string $id): JsonResponse
    {
        $summary = $this->service->getSummary($id);
        return response()->json(['data' => $summary]);
    }

    public function storeGoal(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'goal_text'   => 'required|string',
            'target_date' => 'nullable|date',
            'notes'       => 'nullable|string',
        ]);

        $goal = $this->service->addGoal($id, $validated);
        return response()->json(['data' => $goal], 201);
    }

    public function updateGoal(Request $request, string $id, string $goalId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,achieved,abandoned',
        ]);

        $goal = $this->service->updateGoalStatus($goalId, $validated['status']);
        return response()->json(['data' => $goal]);
    }

    public function storeIntervention(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'intervention_type' => 'required|in:medication,exercise,diet,monitoring,referral,education,other',
            'description'       => 'required|string',
            'frequency'         => 'nullable|string|max:100',
            'responsible_party' => 'nullable|string|max:100',
        ]);

        $intervention = $this->service->addIntervention($id, $validated);
        return response()->json(['data' => $intervention], 201);
    }
}
