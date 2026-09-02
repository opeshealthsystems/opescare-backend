<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DrugInteractionAlertResource;
use App\Models\Visit;
use App\Modules\OperationalFlow\Services\MedicationReconciliationService;
use App\Modules\OperationalFlow\Services\PatientJourneyService;
use App\Modules\OperationalFlow\Services\VisitManagementService;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OperationalFlowController
 *
 * Endpoints:
 *  POST  /v1/operational-flow/patient-journey                          — full pilot journey (existing)
 *
 *  Visit lifecycle (VisitManagementService):
 *  POST  /v1/operational-flow/visits                                   — create visit
 *  GET   /v1/operational-flow/visits/{visit}                           — show visit + allowed transitions
 *  POST  /v1/operational-flow/visits/{visit}/transition                — advance status
 *  POST  /v1/operational-flow/visits/{visit}/complete                  — complete
 *  POST  /v1/operational-flow/visits/{visit}/cancel                    — cancel
 *
 *  Medication reconciliation (MedicationReconciliationService):
 *  POST  /v1/operational-flow/medication-reconciliations               — create reconciliation
 *  POST  /v1/operational-flow/drug-interaction-alerts/{alertId}/acknowledge — acknowledge alert
 */
class OperationalFlowController extends Controller
{
    // ── Visit Management ──────────────────────────────────────────────────

    /**
     * Create a new visit.
     * Body: { patient_id, facility_id?, provider_id?, visit_type? }
     *
     * facility_id is always sourced from middleware attributes.
     * The body value is cross-checked if present (never used as authoritative source).
     */
    public function createVisit(Request $request, VisitManagementService $service): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');

        $validated = $request->validate([
            'patient_id'  => ['required', 'uuid', 'exists:patients,id'],
            'facility_id' => ['nullable', 'uuid'],
            'provider_id' => ['nullable', 'uuid'],
            'visit_type'  => ['nullable', 'string', 'max:100'],
        ]);

        // Cross-check: if body provides facility_id, it must match the middleware value
        if (!empty($validated['facility_id']) && $facilityId && $validated['facility_id'] !== $facilityId) {
            return response()->json([
                'error'   => 'forbidden',
                'message' => __('api.facility_id_mismatch_auth'),
            ], 403);
        }

        // Authoritative facility_id always from middleware
        $effectiveFacilityId = $facilityId ?? $validated['facility_id'];
        if (!$effectiveFacilityId) {
            return response()->json(['message' => __('api.facility_unresolved_auth')], 422);
        }

        $visit = $service->createVisit([
            'patient_id'  => $validated['patient_id'],
            'facility_id' => $effectiveFacilityId,
            'provider_id' => $validated['provider_id'] ?? null,
            'visit_type'  => $validated['visit_type'] ?? 'general',
        ]);

        return response()->json([
            'message' => __('api.visit_created'),
            'data'    => $this->serializeVisit($visit, $service),
        ], 201);
    }

    /**
     * Show a visit with its current allowed transitions.
     * FIX (audit 2026-06-18): Added facility isolation check — visit must belong
     * to the caller's facility. Previously any authenticated client could view
     * visits across facilities (missing authorization scope).
     */
    public function showVisit(Visit $visit, Request $request, VisitManagementService $service): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if ($facilityId && $visit->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.visit_not_found_scope')], 404);
        }
        return response()->json(['data' => $this->serializeVisit($visit, $service)]);
    }

    /**
     * Advance a visit to a new status.
     * Body: { new_status: string, actor_id: uuid }
     * FIX (audit 2026-06-18): Added facility isolation check.
     */
    public function transition(Visit $visit, Request $request, VisitManagementService $service): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if ($facilityId && $visit->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.visit_not_found_scope')], 404);
        }

        $validated = $request->validate([
            'new_status' => ['required', 'string'],
            'actor_id'   => ['required', 'uuid'],
        ]);

        try {
            $visit = $service->transition($visit->id, $validated['new_status'], $validated['actor_id']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.visit_status_updated'),
            'data'    => $this->serializeVisit($visit, $service),
        ]);
    }

    /**
     * Mark a visit as completed.
     * Body: { actor_id: uuid }
     * FIX (audit 2026-06-18): Added facility isolation check.
     * FIX (audit 2026-06-18): Document issuance failure is now logged instead of silently swallowed.
     */
    public function completeVisit(Visit $visit, Request $request, VisitManagementService $service, DocumentIssuanceService $issuance): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if ($facilityId && $visit->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.visit_not_found_scope')], 404);
        }

        $validated = $request->validate([
            'actor_id'            => ['required', 'uuid'],
            'issue_opd_summary'   => ['nullable', 'boolean'],
        ]);

        $visit = $service->complete($visit->id, $validated['actor_id']);

        if ($facilityId && ($validated['issue_opd_summary'] ?? true)) {
            try {
                $issuance->issueFromModel(
                    'OPD',
                    'OPD Visit Summary',
                    ['visit_id' => $visit->id, 'patient_id' => $visit->patient_id, 'visit_type' => $visit->visit_type ?? 'outpatient', 'started_at' => $visit->started_at?->toISOString(), 'ended_at' => $visit->ended_at?->toISOString()],
                    $facilityId,
                    $visit->patient_id,
                    null,
                    $validated['actor_id']
                );
            } catch (\Throwable $e) {
                \Log::warning('visit_document_issuance_failed', [
                    'visit_id'   => $visit->id,
                    'patient_id' => $visit->patient_id,
                    'facility_id'=> $facilityId,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => __('api.visit_completed'),
            'data'    => $this->serializeVisit($visit, $service),
        ]);
    }

    /**
     * Cancel a visit.
     * Body: { actor_id: uuid }
     * FIX (audit 2026-06-18): Added facility isolation check.
     */
    public function cancelVisit(Visit $visit, Request $request, VisitManagementService $service): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if ($facilityId && $visit->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.visit_not_found_scope')], 404);
        }

        $validated = $request->validate([
            'actor_id' => ['required', 'uuid'],
        ]);

        try {
            $visit = $service->cancel($visit->id, $validated['actor_id']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.visit_cancelled'),
            'data'    => $this->serializeVisit($visit, $service),
        ]);
    }

    // ── Medication Reconciliation ─────────────────────────────────────────

    /**
     * Create a medication reconciliation record.
     *
     * CDSS SAFETY: Drug interaction checks are advisory.
     * Hard-stop contraindications (flag_hard_stop=true) will return 422
     * and must not be bypassed without clinical escalation.
     *
     * Body: {
     *   patient_id, provider_id, facility_id?,
     *   medications: [{name, dose?, route?, frequency?, flag_hard_stop?}],
     *   notes?
     * }
     */
    public function createReconciliation(Request $request, MedicationReconciliationService $service): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');

        $validated = $request->validate([
            'patient_id'                    => ['required', 'uuid', 'exists:patients,id'],
            'provider_id'                   => ['required', 'uuid'],
            'facility_id'                   => ['nullable', 'uuid'],
            'medications'                   => ['required', 'array', 'min:1'],
            'medications.*.name'            => ['required', 'string', 'max:255'],
            'medications.*.dose'            => ['nullable', 'string'],
            'medications.*.route'           => ['nullable', 'string'],
            'medications.*.frequency'       => ['nullable', 'string'],
            'medications.*.flag_hard_stop'  => ['nullable', 'boolean'],
            'notes'                         => ['nullable', 'string', 'max:5000'],
        ]);

        if (!empty($validated['facility_id']) && $facilityId && $validated['facility_id'] !== $facilityId) {
            return response()->json([
                'error'   => 'forbidden',
                'message' => __('api.facility_id_mismatch_auth'),
            ], 403);
        }

        $effectiveFacilityId = $facilityId ?? $validated['facility_id'];
        if (!$effectiveFacilityId) {
            return response()->json(['message' => __('api.facility_unresolved_auth')], 422);
        }

        try {
            $reconciliation = $service->createReconciliation(
                $validated['patient_id'],
                $validated['provider_id'],
                $effectiveFacilityId,
                $validated['medications'],
                $validated['notes'] ?? null
            );
        } catch (\Exception $e) {
            // HARD_STOP_CONTRAINDICATION must surface as 422 — never silently swallowed
            return response()->json([
                'message'        => $e->getMessage(),
                'advisory_notice' => 'Alerts are decision-support only. Hard-stop contraindications require clinical review.',
            ], 422);
        }

        $reconciliation->load('drugInteractionAlerts');

        return response()->json([
            'advisory_notice' => 'Drug interaction alerts are advisory only and do not replace clinical judgment.',
            'message'         => __('api.medication_reconciliation_created'),
            'data'            => $reconciliation,
        ], 201);
    }

    /**
     * Acknowledge a drug interaction alert.
     * Body: { user_id: uuid }
     */
    public function acknowledgeDrugAlert(string $alertId, Request $request, MedicationReconciliationService $service): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'uuid'],
        ]);

        try {
            $alert = $service->acknowledge($alertId, $validated['user_id']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => __('api.alert_not_found')], 404);
        }

        return response()->json([
            'message' => __('api.drug_interaction_acknowledged'),
            'data'    => DrugInteractionAlertResource::make($alert),
        ]);
    }

    // ── Existing: Pilot Journey ───────────────────────────────────────────

    public function patientJourney(Request $request, PatientJourneyService $service)
    {
        $result = $service->runPilotJourney($request->validate([
            'patient_id' => ['required', 'uuid'],
            'facility_id' => ['required', 'uuid'],
            'provider_id' => ['required', 'uuid'],
            'appointment_slot_id' => ['required', 'uuid'],
            'appointment_type' => ['required', 'string'],
            'queue_name' => ['required', 'string'],
            'consultation' => ['required', 'array'],
            'consultation.history_of_present_illness' => ['nullable', 'string'],
            'consultation.examination_findings' => ['nullable', 'string'],
            'consultation.treatment_plan' => ['nullable', 'string'],
            'lab_result_summary' => ['nullable', 'string'],
            'invoice_items' => ['required', 'array', 'min:1'],
            'invoice_items.*.description' => ['required', 'string'],
            'invoice_items.*.service_code' => ['nullable', 'string'],
            'invoice_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'invoice_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'invoice_items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'payment' => ['required', 'array'],
            'payment.amount' => ['required', 'numeric', 'min:0.01'],
            'payment.method' => ['required', 'string'],
            'actor_id' => ['nullable', 'uuid'],
        ]));

        return response()->json(['data' => $this->serialize($result)], 201);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function serializeVisit(Visit $visit, VisitManagementService $service): array
    {
        return [
            'id'                  => $visit->id,
            'patient_id'          => $visit->patient_id,
            'facility_id'         => $visit->facility_id,
            'provider_id'         => $visit->provider_id,
            'visit_type'          => $visit->visit_type,
            'status'              => $visit->status,
            'started_at'          => $visit->started_at?->toISOString(),
            'ended_at'            => $visit->ended_at?->toISOString(),
            'allowed_transitions' => $service->allowedTransitions($visit),
        ];
    }

    private function serialize(array $result): array
    {
        return [
            'appointment' => [
                'id' => $result['appointment']->id,
                'status' => $result['appointment']->status,
                'visit_id' => $result['appointment']->visit_id,
            ],
            'queue_ticket' => [
                'id' => $result['queue_ticket']->id,
                'queue_number' => $result['queue_ticket']->queue_number,
                'status' => $result['queue_ticket']->status,
            ],
            'invoice' => [
                'id' => $result['invoice']->id,
                'status' => $result['invoice']->status,
                'balance_amount' => (float) $result['invoice']->balance_amount,
            ],
            'payment' => [
                'id' => $result['payment']->id,
                'status' => $result['payment']->status,
            ],
            'receipt' => [
                'id' => $result['receipt']->id,
                'receipt_number' => $result['receipt']->receipt_number,
            ],
            'document' => [
                'id' => $result['document']->id,
                'document_number' => $result['document']->document_number,
                'verification_code' => $result['document']->verification_code,
            ],
        ];
    }
}
