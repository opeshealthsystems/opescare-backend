<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PalliativeCarePlanResource;
use App\Models\PalliativeCarePlan;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PalliativeCareController extends Controller
{
    public function __construct(private readonly DocumentIssuanceService $issuance) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }

        $validated = $request->validate([
            'patient_id'            => 'required|uuid',
            'lead_clinician_id'     => 'required|uuid',
            'diagnosis'             => 'required|string',
            'prognosis'             => 'nullable|string',
            'goals_of_care'         => 'required|string',
            'pain_management_plan'  => 'nullable|string',
            'symptom_management'    => 'nullable|array',
            'psychological_support' => 'nullable|string',
            'spiritual_support'     => 'nullable|string',
            'family_support'        => 'nullable|string',
            'dnr_status'            => 'nullable|boolean',
            'advance_directive_id'  => 'nullable|uuid',
            'status'                => 'nullable|string|in:active,suspended,completed,withdrawn|max:20',
        ]);

        $record = PalliativeCarePlan::create(array_merge($validated, ['facility_id' => $facilityId]));

        try {
            $this->issuance->issueFromModel(
                'PALL',
                'Palliative Care Plan — ' . $record->id,
                ['plan_id' => $record->id, 'patient_id' => $validated['patient_id']],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['lead_clinician_id'],
            );
        } catch (\Throwable $e) {
            // Non-fatal
        }

        return response()->json(['data' => PalliativeCarePlanResource::make($record)], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }

        $records = PalliativeCarePlan::where('facility_id', $facilityId)
            ->with(['patient', 'leadClinician'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $records]);
    }
}
