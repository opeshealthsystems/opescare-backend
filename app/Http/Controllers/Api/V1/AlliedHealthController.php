<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlliedHealthAssessmentResource;
use App\Models\AlliedHealthAssessment;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlliedHealthController extends Controller
{
    private const DOC_MAP = [
        'physiotherapy'      => ['PHY',  'Physiotherapy Report'],
        'occupational_therapy' => ['OTA', 'Occupational Therapy Assessment'],
        'speech_therapy'     => ['SLT',  'Speech & Language Therapy Report'],
        'nutrition'          => ['NTR',  'Nutritional Assessment'],
        'social_work'        => ['SWA',  'Social Work Assessment'],
    ];

    public function __construct(private readonly DocumentIssuanceService $issuance) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'patient_id'           => ['required', 'uuid', 'exists:patients,id'],
            'therapist_id'         => ['required', 'uuid', 'exists:users,id'],
            'assessment_type'      => ['required', 'in:physiotherapy,occupational_therapy,speech_therapy,nutrition,social_work'],
            'assessment_date'      => ['required', 'date', 'before_or_equal:today'],
            'referral_reason'      => ['nullable', 'string', 'max:500'],
            'subjective_findings'  => ['nullable', 'string'],
            'objective_findings'   => ['nullable', 'string'],
            'assessment_narrative' => ['nullable', 'string'],
            'intervention_plan'    => ['nullable', 'string'],
            'goals'                => ['nullable', 'string', 'max:2000'],
            'sessions_recommended' => ['nullable', 'integer', 'min:1'],
            'follow_up_interval'   => ['nullable', 'string', 'max:100'],
            'outcome_measure'      => ['nullable', 'string', 'max:255'],
        ]);

        $assessment = AlliedHealthAssessment::create(array_merge($validated, [
            'facility_id' => $facilityId,
            'status'      => 'draft',
        ]));

        [$docCode, $docTitle] = self::DOC_MAP[$validated['assessment_type']];

        try {
            $this->issuance->issueFromModel(
                $docCode,
                $docTitle,
                [
                    'assessment_id'    => $assessment->id,
                    'patient_id'       => $validated['patient_id'],
                    'assessment_type'  => $validated['assessment_type'],
                    'assessment_date'  => $validated['assessment_date'],
                    'referral_reason'  => $validated['referral_reason'] ?? null,
                    'intervention_plan' => $validated['intervention_plan'] ?? null,
                    'therapist_id'     => $validated['therapist_id'],
                ],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['therapist_id'],
            );
        } catch (\Throwable) {}

        return response()->json(['data' => AlliedHealthAssessmentResource::make($assessment)], 201);
    }

    public function show(Request $request, AlliedHealthAssessment $assessment): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId || $assessment->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }
        return response()->json(['data' => AlliedHealthAssessmentResource::make($assessment)]);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $query = AlliedHealthAssessment::where('facility_id', $facilityId)
            ->orderByDesc('assessment_date');

        if ($request->filled('assessment_type')) {
            $query->where('assessment_type', $request->query('assessment_type'));
        }
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->query('patient_id'));
        }

        return response()->json(['data' => AlliedHealthAssessmentResource::collection($query->get())]);
    }
}
