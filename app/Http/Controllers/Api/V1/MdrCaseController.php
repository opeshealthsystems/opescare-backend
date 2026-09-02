<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MdrCaseResource;
use App\Models\MdrCase;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MdrCaseController extends Controller
{
    public function __construct(private readonly DocumentIssuanceService $issuance) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }

        $validated = $request->validate([
            'patient_id'              => 'required|uuid',
            'registered_at'           => 'required|date',
            'diagnosis_basis'         => 'required|string|in:culture,molecular,clinical|max:50',
            'drug_resistance_profile' => 'nullable|array',
            'treatment_regimen'       => 'nullable|string|max:100',
            'treatment_start_date'    => 'nullable|date',
            'treatment_end_date'      => 'nullable|date',
            'treatment_outcome'       => 'nullable|string|in:cured,completed,failed,died,lost_to_followup,not_evaluated|max:30',
            'supervising_doctor_id'   => 'nullable|uuid',
            'status'                  => 'nullable|string|max:20',
            'notes'                   => 'nullable|string',
        ]);

        $record = MdrCase::create(array_merge($validated, ['facility_id' => $facilityId]));

        try {
            $this->issuance->issueFromModel(
                'DOTS',
                'MDR-TB Case — ' . $record->id,
                ['mdr_case_id' => $record->id, 'patient_id' => $validated['patient_id']],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['supervising_doctor_id'] ?? null,
            );
        } catch (\Throwable $e) {
            // Non-fatal
        }

        return response()->json(['data' => MdrCaseResource::make($record)], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }

        $records = MdrCase::where('facility_id', $facilityId)
            ->with(['patient', 'supervisingDoctor'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $records]);
    }
}
