<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PediatricRecordResource;
use App\Models\PediatricRecord;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PediatricController extends Controller
{
    private const DOC_MAP = [
        'newborn_assessment'    => ['NBA', 'Newborn Assessment'],
        'child_health_card'     => ['CHC', 'Child Health Card'],
        'growth_chart'          => ['GCH', 'Growth Chart'],
        'stillbirth_certificate' => ['SBC', 'Stillbirth Certificate'],
    ];

    public function __construct(private readonly DocumentIssuanceService $issuance) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'patient_id'              => ['required', 'uuid', 'exists:patients,id'],
            'clinician_id'            => ['required', 'uuid', 'exists:users,id'],
            'record_type'             => ['required', 'in:newborn_assessment,child_health_card,growth_chart,stillbirth_certificate'],
            'record_date'             => ['required', 'date', 'before_or_equal:today'],
            'age_days'                => ['nullable', 'integer', 'min:0'],
            'weight_kg'               => ['nullable', 'numeric', 'min:0.3', 'max:200'],
            'height_cm'               => ['nullable', 'numeric', 'min:20', 'max:250'],
            'head_circumference_cm'   => ['nullable', 'numeric', 'min:10', 'max:70'],
            'apgar_1min'              => ['nullable', 'integer', 'min:0', 'max:10'],
            'apgar_5min'              => ['nullable', 'integer', 'min:0', 'max:10'],
            'gestational_age_weeks'   => ['nullable', 'string', 'max:10'],
            'milestones'              => ['nullable', 'array'],
            'immunisations_given'     => ['nullable', 'array'],
            'growth_data'             => ['nullable', 'array'],
            'clinical_notes'          => ['nullable', 'string'],
        ]);

        $record = PediatricRecord::create(array_merge($validated, [
            'facility_id' => $facilityId,
            'status'      => 'recorded',
        ]));

        [$docCode, $docTitle] = self::DOC_MAP[$validated['record_type']];

        try {
            $this->issuance->issueFromModel(
                $docCode,
                $docTitle,
                [
                    'record_id'             => $record->id,
                    'patient_id'            => $validated['patient_id'],
                    'record_type'           => $validated['record_type'],
                    'record_date'           => $validated['record_date'],
                    'weight_kg'             => $validated['weight_kg'] ?? null,
                    'height_cm'             => $validated['height_cm'] ?? null,
                    'gestational_age_weeks' => $validated['gestational_age_weeks'] ?? null,
                    'clinician_id'          => $validated['clinician_id'],
                ],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['clinician_id'],
            );
        } catch (\Throwable) {}

        return response()->json(['data' => PediatricRecordResource::make($record)], 201);
    }

    public function show(Request $request, PediatricRecord $record): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId || $record->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }
        return response()->json(['data' => PediatricRecordResource::make($record)]);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $query = PediatricRecord::where('facility_id', $facilityId)
            ->orderByDesc('record_date');

        if ($request->filled('record_type')) {
            $query->where('record_type', $request->query('record_type'));
        }
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->query('patient_id'));
        }

        return response()->json(['data' => PediatricRecordResource::collection($query->get())]);
    }
}
