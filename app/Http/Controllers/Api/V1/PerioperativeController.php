<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PerioperativeRecordResource;
use App\Models\PerioperativeRecord;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerioperativeController extends Controller
{
    private const DOC_MAP = [
        'anaesthesia'    => ['ANS', 'Anaesthesia Record'],
        'ssc'            => ['SSC', 'Surgical Safety Checklist'],
        'postop_recovery' => ['POR', 'Post-Operative Recovery Record'],
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
            'provider_id'          => ['required', 'uuid', 'exists:users,id'],
            'record_type'          => ['required', 'in:anaesthesia,ssc,postop_recovery'],
            'procedure_name'       => ['nullable', 'string', 'max:255'],
            'procedure_code'       => ['nullable', 'string', 'max:30'],
            'procedure_datetime'   => ['nullable', 'date'],
            'checklist_data'       => ['nullable', 'array'],
            'anaesthesia_type'     => ['nullable', 'in:general,spinal,epidural,regional,local,sedation,combined'],
            'anaesthesiologist_id' => ['nullable', 'uuid'],
            'intraoperative_notes' => ['nullable', 'string'],
            'postop_notes'         => ['nullable', 'string'],
            'duration_minutes'     => ['nullable', 'integer', 'min:1'],
            'asa_grade'            => ['nullable', 'in:I,II,III,IV,V,VI'],
            'complications'        => ['nullable', 'boolean'],
            'complications_detail' => ['nullable', 'string'],
            'ward'                 => ['nullable', 'string', 'max:100'],
        ]);

        $record = PerioperativeRecord::create(array_merge($validated, [
            'facility_id' => $facilityId,
            'status'      => 'draft',
        ]));

        [$docCode, $docTitle] = self::DOC_MAP[$validated['record_type']];

        try {
            $this->issuance->issueFromModel(
                $docCode,
                $docTitle,
                [
                    'record_id'          => $record->id,
                    'patient_id'         => $validated['patient_id'],
                    'record_type'        => $validated['record_type'],
                    'procedure_name'     => $validated['procedure_name'] ?? null,
                    'procedure_datetime' => $validated['procedure_datetime'] ?? null,
                    'anaesthesia_type'   => $validated['anaesthesia_type'] ?? null,
                    'asa_grade'          => $validated['asa_grade'] ?? null,
                    'complications'      => $validated['complications'] ?? false,
                ],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['provider_id'],
            );
        } catch (\Throwable) {}

        return response()->json(['data' => PerioperativeRecordResource::make($record)], 201);
    }

    public function show(Request $request, PerioperativeRecord $record): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId || $record->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }
        return response()->json(['data' => PerioperativeRecordResource::make($record)]);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $query = PerioperativeRecord::where('facility_id', $facilityId)
            ->orderByDesc('created_at');

        if ($request->filled('record_type')) {
            $query->where('record_type', $request->query('record_type'));
        }
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->query('patient_id'));
        }

        return response()->json(['data' => PerioperativeRecordResource::collection($query->get())]);
    }
}
