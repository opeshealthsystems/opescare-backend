<?php

namespace App\Http\Controllers\Api\V1\Connect;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Notifications\EmergencyAccessAlertNotification;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Modules\Governance\Services\ConsentService;
use App\Modules\Governance\Services\EmergencyAccessService;

/**
 * ConnectGovernanceController
 *
 * Exposes consent management and emergency access governance to B2B
 * integration clients authenticated via auth.bearer middleware.
 *
 * SECURITY:
 *  - facility_id MUST come from $request->attributes->get('facility_id') only.
 *    Never from request headers, body, or hardcoded fallback (OWASP API1 / CLAUDE.md #1).
 *  - Missing facility_id returns 403 — it means the bearer token did not carry
 *    a valid facility scope; the request must not proceed.
 *  - Emergency access always notifies the patient (Cameroon Law No. 2010/012).
 *  - Headers (X-Purpose-Of-Use, X-Emergency-Reason, etc.) are NEVER used to
 *    determine actor identity or authorisation context.
 */
class ConnectGovernanceController extends Controller
{
    public function __construct(
        private readonly ConsentService $consentService,
        private readonly EmergencyAccessService $emergencyService
    ) {}

    // ── Consent ───────────────────────────────────────────────────────────────

    public function requestConsent(Request $request)
    {
        // [C-1 FIX] facility_id from middleware only — no fallback, no header, no body
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'FACILITY_UNRESOLVABLE',
                'message'    => __('api.bearer_no_facility_scope'),
            ], 403);
        }

        // [H-2 FIX] Proper structured validation
        $validated = $request->validate([
            'patient_id'   => ['required', 'string', 'uuid'],
            'purpose'      => ['required', 'string', 'max:100'],
            'scopes'       => ['required', 'array', 'min:1'],
            'scopes.*'     => ['required', 'string', 'max:100'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'], // max 7 days
            'requested_by_user_id' => ['nullable', 'string', 'max:100'],
        ]);

        $consentReq = $this->consentService->requestConsent(
            $validated['patient_id'],
            $facilityId,
            $validated['requested_by_user_id'] ?? null,
            $validated['purpose'],
            $validated['scopes'],
            $validated['duration_minutes'] ?? 240
        );

        return response()->json([
            'status'             => 'requested',
            'consent_request_id' => $consentReq->id,
            'message'            => __('api.consent_request_transmitted'),
        ], 202);
    }

    public function verifyConsent(Request $request)
    {
        // [C-1 FIX] facility_id from middleware only
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'FACILITY_UNRESOLVABLE',
                'message'    => __('api.bearer_no_facility_scope'),
            ], 403);
        }

        $validated = $request->validate([
            'patient_id'              => ['required', 'string', 'uuid'],
            'scope'                   => ['required', 'string', 'max:100'],
            'purpose'                 => ['nullable', 'string', 'max:100'],
            'requested_by_user_id'    => ['nullable', 'string', 'max:100'],
        ]);

        $isValid = $this->consentService->verifyAccess(
            $validated['patient_id'],
            $facilityId,
            $validated['requested_by_user_id'] ?? null,
            $validated['scope'],
            $validated['purpose'] ?? 'treatment'
        );

        return response()->json([
            'is_valid' => $isValid,
            'status'   => $isValid ? 'granted' : 'denied',
        ], 200);
    }

    // ── Emergency access ──────────────────────────────────────────────────────

    public function requestEmergencyAccess(Request $request)
    {
        // [C-1 FIX] facility_id from middleware only
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'FACILITY_UNRESOLVABLE',
                'message'    => __('api.bearer_no_facility_scope'),
            ], 403);
        }

        // [M-2 FIX] reason min:10 — single-word reasons are not legally auditable (MINSANTE)
        $validated = $request->validate([
            'patient_id' => ['required', 'string', 'uuid'],
            'reason'     => ['required', 'string', 'min:10', 'max:1000'],
            // Requesting provider user — must exist (FK-enforced), same convention
            // as requestConsent's requested_by_user_id. Only used when no
            // authenticated provider context is present.
            'actor_id'   => ['nullable', 'string', 'uuid', 'exists:users,id'],
        ]);

        // Prefer actor from authenticated session context; fall back to the
        // validated, existence-checked provider user id supplied by the client.
        $actorId = $request->attributes->get('provider_id')
            ?? ($validated['actor_id'] ?? null);

        if (! $actorId) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'ACTOR_UNRESOLVABLE',
                'message'    => __('api.emergency_requires_actor'),
            ], 422);
        }

        $event = $this->emergencyService->requestEmergencyAccess(
            $validated['patient_id'],
            $facilityId,
            $actorId,
            $validated['reason']
        );

        return response()->json([
            'status'                    => 'emergency_authorized',
            'emergency_access_event_id' => $event->id,
            'message'                   => __('api.emergency_override_activated'),
        ], 201);
    }

    /**
     * Pull emergency profile for a patient by Health ID.
     *
     * [C-1 FIX] facility_id from middleware only — no header fallback.
     * [C-2 FIX] Purpose and reason come from validated request BODY, not headers.
     * [C-3 FIX] Patient MUST be notified (Cameroon Law No. 2010/012).
     */
    public function getEmergencyProfile(Request $request, string $healthId)
    {
        // [C-1 FIX] facility_id from middleware only
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'FACILITY_UNRESOLVABLE',
                'message'    => __('api.bearer_no_facility_scope'),
            ], 403);
        }

        // [C-2 FIX] Purpose and reason from validated body, NEVER from headers
        $validated = $request->validate([
            'purpose'          => ['required', 'string', 'in:emergency,critical_care,resuscitation,mass_casualty'],
            'emergency_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $patient = Patient::where('health_id', strtoupper(trim($healthId)))->first();
        if (! $patient) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'PATIENT_NOT_FOUND',
                'message'    => __('api.patient_not_found'),
            ], 404);
        }

        // Audit BEFORE data is returned [MINSANTE CRITICAL]
        AuditLogger::log(
            $request,
            'emergency_profile_pulled',
            'patient',
            $patient->id,
            $patient->id,
            true,
            $validated['emergency_reason'],
            [
                'purpose'     => $validated['purpose'],
                'facility_id' => $facilityId,
            ]
        );

        // [C-3 FIX] Notify patient asynchronously — Cameroon Law No. 2010/012, Art. 14
        if ($patient->user_id) {
            try {
                $patient->notify(new EmergencyAccessAlertNotification(
                    patientHealthId: $patient->health_id,
                    patientName:     trim($patient->first_name . ' ' . $patient->last_name),
                    accessedAt:      now()->toDateTimeString(),
                    facilityId:      $facilityId,
                    emergencyReason: $validated['emergency_reason'],
                    ipAddress:       $request->ip(),
                ));
            } catch (\Throwable $e) {
                // Notification failure MUST NOT block emergency data access —
                // audit is already written; the notification failure is logged.
                Log::warning('emergency_profile_patient_notification_failed', [
                    'patient_id' => $patient->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        $profile = $this->emergencyService->buildEmergencyProfile($patient->id);
        $profile['emergency_status'] = 'consent_bypassed_audited';

        return response()->json($profile, 200);
    }
}
