<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AntenatalRecordResource;
use App\Http\Resources\AntenatalVisitResource;
use App\Http\Resources\DeliveryRecordResource;
use App\Http\Resources\PregnancyRecordResource;
use App\Models\AntenatalRecord;
use App\Models\PregnancyRecord;
use App\Modules\Maternity\Services\AntenatalCareService;
use App\Modules\Maternity\Services\MaternityService;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaternityController extends Controller
{
    public function __construct(
        private readonly MaternityService       $service,
        private readonly AntenatalCareService   $anc,
        private readonly DocumentIssuanceService $issuance
    ) {}

    public function index(string $patientId): JsonResponse
    {
        $records = PregnancyRecord::where('patient_id', $patientId)
            ->with(['facility', 'provider'])
            ->orderByDesc('registered_at')
            ->get();

        return response()->json(['data' => PregnancyRecordResource::collection($records)]);
    }

    public function store(Request $request, string $patientId): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'provider_id'      => ['required', 'uuid', 'exists:users,id'],
            'gravida'          => ['required', 'integer', 'min:1'],
            'para'             => ['required', 'integer', 'min:0'],
            'lmp'              => ['nullable', 'date'],
            'edd'              => ['nullable', 'date'],
            'pregnancy_status' => ['required', 'in:active,delivered,miscarriage,stillbirth,ectopic,terminated'],
            'blood_type'       => ['nullable', 'in:A,B,AB,O'],
            'rhesus_factor'    => ['nullable', 'in:positive,negative'],
            'high_risk'        => ['boolean'],
            'risk_factors'     => ['nullable', 'array'],
            'notes'            => ['nullable', 'string'],
            'registered_at'    => ['nullable', 'date'],
        ]);

        $validated['patient_id']    = $patientId;
        $validated['facility_id']   = $facilityId;
        $validated['registered_at'] = $validated['registered_at'] ?? now();

        $record = $this->service->registerPregnancy($validated);

        return response()->json(['data' => PregnancyRecordResource::make($record)], 201);
    }

    public function show(string $id): JsonResponse
    {
        $record = PregnancyRecord::with(['patient', 'facility', 'provider'])->findOrFail($id);
        return response()->json(['data' => PregnancyRecordResource::make($record)]);
    }

    public function antenatalVisits(string $id): JsonResponse
    {
        $record = PregnancyRecord::findOrFail($id);
        $visits = $record->antenatalVisits()->with('provider')->orderBy('visit_date')->get();
        return response()->json(['data' => AntenatalVisitResource::collection($visits)]);
    }

    public function storeAntenatalVisit(Request $request, string $id): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'patient_id'            => ['required', 'uuid', 'exists:patients,id'],
            'provider_id'           => ['required', 'uuid', 'exists:users,id'],
            'visit_date'            => ['required', 'date'],
            'gestational_age_weeks' => ['required', 'integer', 'min:0', 'max:45'],
            'gestational_age_days'  => ['required', 'integer', 'min:0', 'max:6'],
            'fundal_height_cm'      => ['nullable', 'numeric', 'min:0'],
            'fetal_heart_rate'      => ['nullable', 'integer', 'min:60', 'max:200'],
            'presentation'          => ['nullable', 'in:cephalic,breech,transverse,unknown'],
            'weight_kg'             => ['nullable', 'numeric', 'min:20', 'max:200'],
            'bp_systolic'           => ['nullable', 'integer', 'min:60', 'max:250'],
            'bp_diastolic'          => ['nullable', 'integer', 'min:40', 'max:150'],
            'urine_protein'         => ['nullable', 'in:negative,trace,1+,2+,3+,4+'],
            'urine_glucose'         => ['nullable', 'in:negative,trace,positive'],
            'oedema'                => ['nullable', 'in:none,mild,moderate,severe'],
            'notes'                 => ['nullable', 'string'],
        ]);

        $validated['facility_id'] = $facilityId;
        $visit = $this->service->recordAntenatalVisit($id, $validated);
        return response()->json(['data' => AntenatalVisitResource::make($visit)], 201);
    }

    public function deliveries(string $id): JsonResponse
    {
        $record     = PregnancyRecord::findOrFail($id);
        $deliveries = $record->deliveryRecords()->with('provider')->get();
        return response()->json(['data' => DeliveryRecordResource::collection($deliveries)]);
    }

    // ── Antenatal Care Records (AntenatalCareService) ─────────────────────────

    /**
     * Open a new dedicated ANC record.
     * Body: { patient_id, provider_id, lmp_date, gravida, para, risk_factors? }
     * facility_id from middleware attributes.
     */
    public function openAntenatalRecord(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved_id')], 422);
        }

        $validated = $request->validate([
            'patient_id'   => ['required', 'uuid', 'exists:patients,id'],
            'provider_id'  => ['required', 'uuid', 'exists:users,id'],
            'lmp_date'     => ['required', 'date', 'before_or_equal:today'],
            'gravida'      => ['required', 'integer', 'min:1'],
            'para'         => ['required', 'integer', 'min:0'],
            'risk_factors' => ['nullable', 'string', 'max:1000'],
        ]);

        $record = $this->anc->openRecord(
            $validated['patient_id'],
            $validated['provider_id'],
            $facilityId,
            $validated['lmp_date'],
            $validated['gravida'],
            $validated['para'],
            $validated['risk_factors'] ?? null
        );

        return response()->json(['data' => AntenatalRecordResource::make($record)], 201);
    }

    /**
     * Record an ANC visit against an AntenatalRecord.
     * Body: { provider_id, visit_date, gestational_age_weeks, blood_pressure?, fetal_heart_rate?,
     *         weight_kg?, fundal_height?, presentation?, notes?, next_visit_plan? }
     */
    public function recordAncVisit(Request $request, string $recordId): JsonResponse
    {
        $validated = $request->validate([
            'provider_id'          => ['required', 'uuid', 'exists:users,id'],
            'visit_date'           => ['required', 'date', 'before_or_equal:today'],
            'gestational_age'      => ['required', 'integer', 'min:4', 'max:45'],
            'blood_pressure'       => ['nullable', 'string', 'regex:/^\d{2,3}\/\d{2,3}$/'],
            'fetal_heart_rate'     => ['nullable', 'integer', 'min:60', 'max:200'],
            'weight_kg'            => ['nullable', 'numeric', 'min:20', 'max:200'],
            'fundal_height'        => ['nullable', 'numeric', 'min:0', 'max:50'],
            'presentation'         => ['nullable', 'in:cephalic,breech,transverse,unknown'],
            'notes'                => ['nullable', 'string'],
            'next_visit_plan'      => ['nullable', 'string', 'max:500'],
        ]);

        $facilityId = $request->attributes->get('facility_id');
        $antenatal  = AntenatalRecord::findOrFail($recordId); // 404 guard

        $visit = $this->anc->recordVisit(
            $recordId,
            $validated['provider_id'],
            $validated['visit_date'],
            $validated['gestational_age'],
            $validated['blood_pressure']   ?? null,
            $validated['fetal_heart_rate'] ?? null,
            $validated['weight_kg']        ?? null,
            $validated['fundal_height']    ?? null,
            $validated['presentation']     ?? null,
            $validated['notes']            ?? null,
            $validated['next_visit_plan']  ?? null,
        );

        if ($facilityId) {
            try {
                $visitId = is_array($visit) ? ($visit['id'] ?? null) : ($visit->id ?? null);
                $this->issuance->issueFromModel(
                    'ANC',
                    'Antenatal Visit Record',
                    ['visit_id' => $visitId, 'antenatal_record_id' => $recordId, 'patient_id' => $antenatal->patient_id, 'visit_date' => $validated['visit_date'], 'gestational_age' => $validated['gestational_age'], 'blood_pressure' => $validated['blood_pressure'] ?? null, 'next_visit_plan' => $validated['next_visit_plan'] ?? null],
                    $facilityId,
                    $antenatal->patient_id,
                    null,
                    $validated['provider_id']
                );
            } catch (\Throwable) {}
        }

        return response()->json(['data' => AntenatalVisitResource::make($visit)], 201);
    }

    // ── Postnatal Care ───────────────────────────────────────────────────────

    /**
     * Record a postnatal care visit.
     * Body: { patient_id, provider_id, visit_date, days_postpartum, bp_systolic?, bp_diastolic?,
     *         weight_kg?, lochia?, wound_healing?, breastfeeding_status?, infant_weight_grams?,
     *         notes?, next_visit_plan? }
     */
    public function recordPostnatalVisit(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'patient_id'           => ['required', 'uuid', 'exists:patients,id'],
            'provider_id'          => ['required', 'uuid', 'exists:users,id'],
            'visit_date'           => ['required', 'date', 'before_or_equal:today'],
            'days_postpartum'      => ['required', 'integer', 'min:0', 'max:365'],
            'bp_systolic'          => ['nullable', 'integer', 'min:60', 'max:250'],
            'bp_diastolic'         => ['nullable', 'integer', 'min:40', 'max:150'],
            'weight_kg'            => ['nullable', 'numeric', 'min:20', 'max:200'],
            'lochia'               => ['nullable', 'in:rubra,serosa,alba,none'],
            'wound_healing'        => ['nullable', 'in:normal,delayed,infected,dehisced'],
            'breastfeeding_status' => ['nullable', 'in:exclusive,partial,none'],
            'infant_weight_grams'  => ['nullable', 'integer', 'min:500', 'max:10000'],
            'notes'                => ['nullable', 'string'],
            'next_visit_plan'      => ['nullable', 'string', 'max:500'],
        ]);

        $validated['facility_id'] = $facilityId;
        $visit = $this->service->recordPostnatalVisit($validated);

        try {
            $visitId = is_array($visit) ? ($visit['id'] ?? null) : ($visit->id ?? null);
            $this->issuance->issueFromModel(
                'PNC',
                'Postnatal Care Record',
                [
                    'visit_id'             => $visitId,
                    'patient_id'           => $validated['patient_id'],
                    'visit_date'           => $validated['visit_date'],
                    'days_postpartum'      => $validated['days_postpartum'],
                    'breastfeeding_status' => $validated['breastfeeding_status'] ?? null,
                    'wound_healing'        => $validated['wound_healing'] ?? null,
                    'next_visit_plan'      => $validated['next_visit_plan'] ?? null,
                ],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['provider_id'],
            );
        } catch (\Throwable) {}

        return response()->json(['data' => $visit], 201);
    }

    // ── Deliveries ──────────────────────────────────────────────────────────

    public function storeDelivery(Request $request, string $id): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'patient_id'            => ['required', 'uuid', 'exists:patients,id'],
            'provider_id'           => ['required', 'uuid', 'exists:users,id'],
            'delivery_date'         => ['required', 'date'],
            'delivery_mode'         => ['required', 'in:svd,assisted_vaginal,caesarean,other'],
            'indication'            => ['nullable', 'string'],
            'duration_labour_hours' => ['nullable', 'numeric', 'min:0'],
            'birth_weight_grams'    => ['required', 'integer', 'min:300', 'max:7000'],
            'apgar_1min'            => ['nullable', 'integer', 'min:0', 'max:10'],
            'apgar_5min'            => ['nullable', 'integer', 'min:0', 'max:10'],
            'neonatal_outcome'      => ['required', 'in:live,stillbirth,early_neonatal_death'],
            'complications'         => ['nullable', 'string'],
            'notes'                 => ['nullable', 'string'],
        ]);

        $validated['facility_id'] = $facilityId;
        $delivery = $this->service->recordDelivery($id, $validated);

        try {
            $deliveryId = is_array($delivery) ? ($delivery['id'] ?? null) : ($delivery->id ?? null);
            $this->issuance->issueFromModel(
                'BNF',
                'Birth Notification',
                ['delivery_id' => $deliveryId, 'patient_id' => $validated['patient_id'], 'delivery_date' => $validated['delivery_date'], 'delivery_mode' => $validated['delivery_mode'], 'neonatal_outcome' => $validated['neonatal_outcome'], 'birth_weight_grams' => $validated['birth_weight_grams']],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['provider_id'] ?? null
            );
        } catch (\Throwable) {}

        return response()->json(['data' => DeliveryRecordResource::make($delivery)], 201);
    }
}
