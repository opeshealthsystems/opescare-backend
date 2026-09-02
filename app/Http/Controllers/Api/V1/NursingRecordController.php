<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NursingRecordResource;
use App\Models\NursingRecord;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NursingRecordController extends Controller
{
    private const DOC_MAP = [
        'mar'                  => ['MAR', 'Medication Administration Record'],
        'progress'             => ['PRG', 'Daily Progress Note'],
        'handover'             => ['HOV', 'Handover Note'],
        'admission_assessment' => ['NAA', 'Nursing Admission Assessment'],
        'wound'                => ['WND', 'Wound Care Chart'],
        'incident'             => ['INC', 'Incident Report'],
        'fall_risk'            => ['FRA', 'Fall Risk Assessment'],
        'pressure_ulcer'       => ['PUA', 'Pressure Ulcer Assessment'],
    ];

    public function __construct(private readonly DocumentIssuanceService $issuance) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'patient_id'  => ['required', 'uuid', 'exists:patients,id'],
            'nurse_id'    => ['required', 'uuid', 'exists:users,id'],
            'record_type' => ['required', 'in:mar,progress,handover,admission_assessment,wound,incident,fall_risk,pressure_ulcer'],
            'record_date' => ['required', 'date', 'before_or_equal:today'],
            'content'     => ['required', 'array'],
            'ward'        => ['nullable', 'string', 'max:100'],
            'bed_number'  => ['nullable', 'string', 'max:20'],
            'shift'       => ['nullable', 'in:morning,afternoon,night'],
        ]);

        $record = NursingRecord::create(array_merge($validated, [
            'facility_id' => $facilityId,
            'status'      => 'recorded',
        ]));

        [$docCode, $docTitle] = self::DOC_MAP[$validated['record_type']];

        try {
            $this->issuance->issueFromModel(
                $docCode,
                $docTitle,
                [
                    'record_id'   => $record->id,
                    'patient_id'  => $validated['patient_id'],
                    'record_type' => $validated['record_type'],
                    'record_date' => $validated['record_date'],
                    'ward'        => $validated['ward'] ?? null,
                    'shift'       => $validated['shift'] ?? null,
                    'nurse_id'    => $validated['nurse_id'],
                ],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['nurse_id'],
            );
        } catch (\Throwable) {}

        return response()->json(['data' => NursingRecordResource::make($record)], 201);
    }

    public function show(Request $request, NursingRecord $record): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId || $record->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }
        return response()->json(['data' => NursingRecordResource::make($record)]);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $query = NursingRecord::where('facility_id', $facilityId)
            ->orderByDesc('record_date');

        if ($request->filled('record_type')) {
            $query->where('record_type', $request->query('record_type'));
        }
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->query('patient_id'));
        }

        return response()->json(['data' => NursingRecordResource::collection($query->get())]);
    }
}
