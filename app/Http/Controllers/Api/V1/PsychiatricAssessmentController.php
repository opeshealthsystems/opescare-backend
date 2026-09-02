<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PsychiatricAssessmentResource;
use App\Models\PsychiatricAssessment;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PsychiatricAssessmentController extends Controller
{
    public function __construct(private readonly DocumentIssuanceService $issuance) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'patient_id'                 => ['required', 'uuid', 'exists:patients,id'],
            'clinician_id'               => ['required', 'uuid', 'exists:users,id'],
            'assessment_date'            => ['required', 'date', 'before_or_equal:today'],
            'referral_source'            => ['nullable', 'string', 'max:100'],
            'presenting_complaints'      => ['nullable', 'array'],
            'psychiatric_history'        => ['nullable', 'string'],
            'family_history'             => ['nullable', 'string'],
            'substance_use_history'      => ['nullable', 'string'],
            'mental_state_examination'   => ['nullable', 'string'],
            'risk_factors'               => ['nullable', 'array'],
            'diagnosis_icd'              => ['nullable', 'string', 'max:30'],
            'diagnosis_narrative'        => ['nullable', 'string'],
            'management_plan'            => ['nullable', 'string'],
            'medications_current'        => ['nullable', 'array'],
            'risk_level'                 => ['nullable', 'in:low,medium,high,very_high'],
            'follow_up_plan'             => ['nullable', 'string', 'max:500'],
            'notes'                      => ['nullable', 'string'],
        ]);

        $assessment = PsychiatricAssessment::create(array_merge($validated, [
            'facility_id' => $facilityId,
            'status'      => 'draft',
        ]));

        try {
            $this->issuance->issueFromModel(
                'PSY',
                'Psychiatric Assessment',
                [
                    'assessment_id'     => $assessment->id,
                    'patient_id'        => $validated['patient_id'],
                    'assessment_date'   => $validated['assessment_date'],
                    'diagnosis_icd'     => $validated['diagnosis_icd'] ?? null,
                    'diagnosis_narrative' => $validated['diagnosis_narrative'] ?? null,
                    'risk_level'        => $validated['risk_level'] ?? null,
                    'clinician_id'      => $validated['clinician_id'],
                ],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['clinician_id'],
            );
        } catch (\Throwable) {}

        return response()->json(['data' => PsychiatricAssessmentResource::make($assessment)], 201);
    }

    public function show(Request $request, PsychiatricAssessment $assessment): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId || $assessment->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }
        return response()->json(['data' => PsychiatricAssessmentResource::make($assessment)]);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $query = PsychiatricAssessment::where('facility_id', $facilityId)
            ->orderByDesc('assessment_date');

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->query('patient_id'));
        }

        return response()->json(['data' => PsychiatricAssessmentResource::collection($query->get())]);
    }
}
