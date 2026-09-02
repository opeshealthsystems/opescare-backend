<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WardAdminRecordResource;
use App\Models\WardAdminRecord;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WardAdminController extends Controller
{
    private const DOC_MAP = [
        'lama'                     => ['LAMA', 'Leave Against Medical Advice Form'],
        'transfer'                 => ['TRF',  'Transfer Letter'],
        'investigation_request'    => ['REQ',  'Investigation Request Form'],
        'patient_complaint'        => ['PCF',  'Patient Complaint Form'],
        'procedure_consent'        => ['PCS',  'Procedure Consent Form'],
        'pharmacy_dispensing'      => ['DPR',  'Pharmacy Dispensing Record'],
        'medication_reconciliation' => ['MRC', 'Medication Reconciliation Record'],
        'blood_transfusion'        => ['BTR',  'Blood Transfusion Record'],
        'blood_bank_request'       => ['BBR',  'Blood Bank Request'],
        'glucose_log'              => ['DGL',  'Glucose Monitoring Log'],
        'arv_card'                 => ['ARV',  'ARV Treatment Card'],
        'fitness_certificate'      => ['FIT',  'Fitness Certificate'],
        'orthopaedic_chart'        => ['ORT',  'Orthopaedic Chart'],
        'resuscitation'            => ['CPR',  'Resuscitation Record'],
        'mental_health_involuntary' => ['MHI', 'Mental Health Involuntary Admission Order'],
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
            'actor_id'    => ['required', 'uuid', 'exists:users,id'],
            'record_type' => ['required', 'in:lama,transfer,investigation_request,patient_complaint,procedure_consent,pharmacy_dispensing,medication_reconciliation,blood_transfusion,blood_bank_request,glucose_log,arv_card,fitness_certificate,orthopaedic_chart,resuscitation,mental_health_involuntary'],
            'record_date' => ['required', 'date', 'before_or_equal:today'],
            'content'     => ['required', 'array'],
        ]);

        $record = WardAdminRecord::create(array_merge($validated, [
            'facility_id' => $facilityId,
            'status'      => 'recorded',
        ]));

        [$docCode, $docTitle] = self::DOC_MAP[$validated['record_type']];

        try {
            $this->issuance->issueFromModel(
                $docCode,
                $docTitle,
                array_merge(
                    ['record_id' => $record->id, 'patient_id' => $validated['patient_id'], 'record_type' => $validated['record_type'], 'record_date' => $validated['record_date']],
                    $validated['content'],
                ),
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['actor_id'],
            );
        } catch (\Throwable) {}

        return response()->json(['data' => WardAdminRecordResource::make($record)], 201);
    }

    public function show(Request $request, WardAdminRecord $record): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId || $record->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }
        return response()->json(['data' => WardAdminRecordResource::make($record)]);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $query = WardAdminRecord::where('facility_id', $facilityId)
            ->orderByDesc('record_date');

        if ($request->filled('record_type')) {
            $query->where('record_type', $request->query('record_type'));
        }
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->query('patient_id'));
        }

        return response()->json(['data' => WardAdminRecordResource::collection($query->get())]);
    }
}
