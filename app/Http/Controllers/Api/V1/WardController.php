<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Modules\WardManagement\Services\AdmissionService;
use App\Modules\WardManagement\Services\WardService;
use App\Modules\WardManagement\Services\DischargePlanningService;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * WardController — Ward, Admission & Bed Management API.
 *
 * Covers patient admission, bed assignment, ward transfers,
 * nursing rounds, inpatient medication administration, and discharge planning.
 *
 * CDSS SAFETY RULE: Medication administration records are advisory tools.
 * Clinical staff retain full responsibility for all administration decisions.
 */
class WardController extends Controller
{
    public function __construct(
        private AdmissionService         $admissions,
        private WardService              $wards,
        private DischargePlanningService $discharge,
        private DocumentIssuanceService  $issuance
    ) {}

    // ── Admissions ─────────────────────────────────────────────────────────

    public function admit(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');

        $validated = $request->validate([
            'patient_id'       => ['required', 'uuid'],
            'facility_id'      => ['required', 'uuid'],
            'ward_id'          => ['nullable', 'uuid'],
            'bed_id'           => ['nullable', 'uuid'],
            'admission_reason' => ['required', 'string'],
            'admitting_doctor' => ['required', 'uuid'],
            'admission_type'   => ['required', 'in:emergency,elective,transfer'],
        ]);

        $result = $this->admissions->admit($validated, $request->user()->id);

        if ($facilityId) {
            try {
                $admissionId = is_array($result) ? ($result['id'] ?? null) : ($result->id ?? null);
                $this->issuance->issueFromModel(
                    'ADM',
                    'Admission Form',
                    ['admission_id' => $admissionId, 'patient_id' => $validated['patient_id'], 'admission_type' => $validated['admission_type'], 'admission_reason' => $validated['admission_reason'], 'admitting_doctor' => $validated['admitting_doctor']],
                    $facilityId,
                    $validated['patient_id'],
                    null,
                    $validated['admitting_doctor']
                );
            } catch (\Throwable) {}
        }

        return response()->json($result, 201);
    }

    public function assignBed(Request $request, string $admissionId): JsonResponse
    {
        $validated = $request->validate([
            'bed_id'  => ['required', 'uuid'],
            'notes'   => ['nullable', 'string'],
        ]);

        $admission = Admission::findOrFail($admissionId);

        return response()->json(
            $this->admissions->transferBed($admission, $validated['bed_id'], $validated['notes'] ?? 'Initial bed assignment', $request->user()->id)
        );
    }

    public function transferBed(Request $request, string $admissionId): JsonResponse
    {
        $validated = $request->validate([
            'new_bed_id' => ['required', 'uuid'],
            'reason'     => ['required', 'string'],
        ]);

        $admission = Admission::findOrFail($admissionId);

        return response()->json(
            $this->wards->transfer($admission, $validated['new_bed_id'], $validated['reason'], $request->user()->id)
        );
    }

    // ── Bed Availability ───────────────────────────────────────────────────

    public function getBedAvailability(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        return response()->json(
            $this->wards->getBedAvailability($facilityId, $request->input('ward_id'))
        );
    }

    // ── Nursing Rounds ─────────────────────────────────────────────────────

    public function recordNursingRound(Request $request, string $admissionId): JsonResponse
    {
        $validated = $request->validate([
            'notes'      => ['required', 'string'],
            'vitals'     => ['nullable', 'array'],
            'concerns'   => ['nullable', 'string'],
        ]);

        return response()->json(
            $this->wards->recordNursingRound($admissionId, $validated, $request->user()->id),
            201
        );
    }

    // ── Discharge Planning ─────────────────────────────────────────────────

    public function initiateDischargePlan(Request $request, string $admissionId): JsonResponse
    {
        $validated = $request->validate([
            'planned_discharge_date' => ['required', 'date', 'after:today'],
            'discharge_notes'        => ['nullable', 'string'],
            'follow_up_required'     => ['required', 'boolean'],
        ]);

        return response()->json(
            $this->discharge->initiatePlan($admissionId, $validated, $request->user()->id),
            201
        );
    }

    public function discharge(Request $request, string $admissionId): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');

        $validated = $request->validate([
            'discharge_summary' => ['required', 'string'],
            'discharge_type'    => ['required', 'in:home,transfer,against_advice,deceased'],
            'follow_up_date'    => ['nullable', 'date', 'after:today'],
        ]);

        $admission = Admission::findOrFail($admissionId);
        $result = $this->admissions->discharge(
            $admission,
            $validated['discharge_summary'],
            $validated['discharge_type'],
            $request->user()->id
        );

        if ($facilityId) {
            try {
                $admission = Admission::find($admissionId);
                $this->issuance->issueFromModel(
                    'DIS',
                    'Discharge Summary',
                    ['admission_id' => $admissionId, 'discharge_type' => $validated['discharge_type'], 'discharge_summary' => $validated['discharge_summary'], 'follow_up_date' => $validated['follow_up_date'] ?? null],
                    $facilityId,
                    $admission?->patient_id,
                    null,
                    $request->user()?->id
                );
            } catch (\Throwable) {}
        }

        return response()->json($result);
    }
}
