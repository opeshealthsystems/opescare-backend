<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SpecialCareRecordResource;
use App\Models\SpecialCareRecord;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpecialCareController extends Controller
{
    private const DOC_MAP = [
        'icu'          => ['ICU', 'ICU Flowsheet'],
        'nicu'         => ['NIC', 'NICU Chart'],
        'dialysis'     => ['DLY', 'Dialysis Record'],
        'chemotherapy' => ['CTX', 'Chemotherapy Record'],
    ];

    public function __construct(private readonly DocumentIssuanceService $issuance) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'patient_id'       => ['required', 'uuid', 'exists:patients,id'],
            'clinician_id'     => ['required', 'uuid', 'exists:users,id'],
            'care_type'        => ['required', 'in:icu,nicu,dialysis,chemotherapy'],
            'record_date'      => ['required', 'date', 'before_or_equal:today'],
            'session_number'   => ['nullable', 'integer', 'min:1'],
            'vitals'           => ['nullable', 'array'],
            'medications'      => ['nullable', 'array'],
            'observations'     => ['nullable', 'array'],
            'clinical_notes'   => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'outcome'          => ['nullable', 'string', 'max:100'],
        ]);

        $record = SpecialCareRecord::create(array_merge($validated, [
            'facility_id' => $facilityId,
            'status'      => 'recorded',
        ]));

        [$docCode, $docTitle] = self::DOC_MAP[$validated['care_type']];

        try {
            $this->issuance->issueFromModel(
                $docCode,
                $docTitle,
                [
                    'record_id'      => $record->id,
                    'patient_id'     => $validated['patient_id'],
                    'care_type'      => $validated['care_type'],
                    'record_date'    => $validated['record_date'],
                    'session_number' => $validated['session_number'] ?? null,
                    'outcome'        => $validated['outcome'] ?? null,
                    'clinician_id'   => $validated['clinician_id'],
                ],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['clinician_id'],
            );
        } catch (\Throwable) {}

        return response()->json(['data' => SpecialCareRecordResource::make($record)], 201);
    }

    public function show(Request $request, SpecialCareRecord $record): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId || $record->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }
        return response()->json(['data' => SpecialCareRecordResource::make($record)]);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $query = SpecialCareRecord::where('facility_id', $facilityId)
            ->orderByDesc('record_date');

        if ($request->filled('care_type')) {
            $query->where('care_type', $request->query('care_type'));
        }
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->query('patient_id'));
        }

        return response()->json(['data' => SpecialCareRecordResource::collection($query->get())]);
    }
}
