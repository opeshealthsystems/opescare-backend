<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HivCounsellingSessionResource;
use App\Models\HivCounsellingSession;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HivCounsellingController extends Controller
{
    public function __construct(private readonly DocumentIssuanceService $issuance) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }

        $validated = $request->validate([
            'patient_id'        => 'required|uuid',
            'counsellor_id'     => 'required|uuid',
            'session_type'      => 'required|string|in:pre_test,post_test,adherence,disclosure|max:30',
            'session_date'      => 'required|date',
            'test_result'       => 'nullable|string|in:positive,negative,indeterminate,not_tested|max:20',
            'cd4_count'         => 'nullable|integer|min:0',
            'viral_load'        => 'nullable|integer|min:0',
            'on_art'            => 'nullable|boolean',
            'art_regimen'       => 'nullable|string|max:100',
            'risk_factors'      => 'nullable|array',
            'counselling_notes' => 'nullable|string',
            'follow_up_date'    => 'nullable|date',
            'consent_obtained'  => 'required|boolean',
        ]);

        $record = HivCounsellingSession::create(array_merge($validated, ['facility_id' => $facilityId]));

        try {
            $this->issuance->issueFromModel(
                'HCR',
                'HIV Counselling Session — ' . $validated['session_type'],
                ['session_id' => $record->id, 'patient_id' => $validated['patient_id']],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['counsellor_id'],
            );
        } catch (\Throwable $e) {
            // Non-fatal
        }

        return response()->json(['data' => HivCounsellingSessionResource::make($record)], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }

        $records = HivCounsellingSession::where('facility_id', $facilityId)
            ->with(['patient', 'counsellor'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => HivCounsellingSessionResource::collection($records)]);
    }
}
