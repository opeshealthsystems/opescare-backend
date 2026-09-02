<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClinicalReviewRecordResource;
use App\Models\ClinicalReviewRecord;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClinicalReviewController extends Controller
{
    private const DOC_MAP = [
        'maternal_death_review'      => ['MDR',  'Maternal Death Review'],
        'perinatal_mortality_review' => ['PMV',  'Perinatal Mortality Review'],
        'coroners_notification'      => ['CMN',  "Coroner's Notification"],
        'verbal_autopsy'             => ['VBA',  'Verbal Autopsy'],
        'adverse_event'              => ['AER',  'Adverse Event Report'],
        'medicolegal'                => ['MLR',  'Medicolegal Report'],
        'notifiable_disease'         => ['NDR',  'Notifiable Disease Report'],
        'malaria_case'               => ['MAL',  'Malaria Case Report'],
    ];

    public function __construct(private readonly DocumentIssuanceService $issuance) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'reviewer_id'              => ['required', 'uuid', 'exists:users,id'],
            'patient_id'               => ['nullable', 'uuid'],
            'review_type'              => ['required', 'in:maternal_death_review,perinatal_mortality_review,coroners_notification,verbal_autopsy,adverse_event,medicolegal,notifiable_disease,malaria_case'],
            'review_date'              => ['required', 'date', 'before_or_equal:today'],
            'case_reference'           => ['nullable', 'string', 'max:100'],
            'summary'                  => ['required', 'string'],
            'findings'                 => ['nullable', 'array'],
            'recommendations'          => ['nullable', 'array'],
            'outcome'                  => ['nullable', 'string', 'max:255'],
            'reported_to_authority'    => ['nullable', 'string', 'max:255'],
        ]);

        $record = ClinicalReviewRecord::create(array_merge($validated, [
            'facility_id' => $facilityId,
            'status'      => 'draft',
        ]));

        [$docCode, $docTitle] = self::DOC_MAP[$validated['review_type']];

        try {
            $this->issuance->issueFromModel(
                $docCode,
                $docTitle,
                [
                    'record_id'    => $record->id,
                    'patient_id'   => $validated['patient_id'] ?? null,
                    'review_type'  => $validated['review_type'],
                    'review_date'  => $validated['review_date'],
                    'case_reference' => $validated['case_reference'] ?? null,
                    'outcome'      => $validated['outcome'] ?? null,
                    'reviewer_id'  => $validated['reviewer_id'],
                ],
                $facilityId,
                $validated['patient_id'] ?? null,
                null,
                $validated['reviewer_id'],
            );
        } catch (\Throwable) {}

        return response()->json(['data' => ClinicalReviewRecordResource::make($record)], 201);
    }

    public function show(Request $request, ClinicalReviewRecord $record): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId || $record->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }
        return response()->json(['data' => ClinicalReviewRecordResource::make($record)]);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $query = ClinicalReviewRecord::where('facility_id', $facilityId)
            ->orderByDesc('review_date');

        if ($request->filled('review_type')) {
            $query->where('review_type', $request->query('review_type'));
        }

        return response()->json(['data' => ClinicalReviewRecordResource::collection($query->get())]);
    }
}
