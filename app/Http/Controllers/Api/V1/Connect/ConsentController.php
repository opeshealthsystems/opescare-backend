<?php

namespace App\Http\Controllers\Api\V1\Connect;

use App\Enums\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Connect\RequestConsentRequest;
use App\Models\ConsentRequest;
use App\Models\Patient;
use App\Services\Identity\HealthIdAuditLogger;
use Illuminate\Http\Request;

/**
 * Consent Controller — B2B Connect API
 *
 * External integration clients (HIS, pharmacy, lab, insurance) use this
 * endpoint to request patient consent before accessing clinical data.
 *
 * Security hardening (audit sprint):
 * - Validator::make() replaced by RequestConsentRequest Form Request (Wave 4)
 * - Private logAccess() retired; all writes go through HealthIdAuditLogger
 * - Status checks use enum-safe isBlocked() — NOT raw string comparisons,
 *   which silently fail after Wave 4 added enum casts to Patient::$casts
 * - Checks extended to cover merged and erasure_pending statuses
 * - facility_id and actor resolved exclusively from middleware attributes
 */
class ConsentController extends Controller
{
    public function __construct(private readonly HealthIdAuditLogger $auditor) {}

    public function requestConsent(RequestConsentRequest $request)
    {
        $validated = $request->validated();

        // ── Caller identity from VerifyIntegrationClient middleware ───────────
        $clientId   = $request->attributes->get('integration_client_id');
        $facilityId = $request->attributes->get('facility_id');
        $actorId    = $request->attributes->get('actor_id', $clientId);

        // ── 1. Verify patient exists ──────────────────────────────────────────
        $patient = Patient::where('health_id', $validated['health_id'])->first();

        if (! $patient) {
            $this->auditor->denied(
                request:   $request,
                eventType: AuditEventType::ConsentDenied,
                healthId:  $validated['health_id'],
                patientId: null,
                notes:     'HEALTH_ID_NOT_FOUND',
                facilityId: $facilityId,
            );
            return response()->json([
                'status'     => 'invalid',
                'error_code' => 'HEALTH_ID_NOT_FOUND',
                'message'    => __('api.health_id_unverified'),
            ], 404);
        }

        // ── 2. Status gate — enum-safe blocked check ──────────────────────────
        // CRITICAL: After Wave 4 enum casts, $patient->verification_status is a
        // VerificationStatus enum object. in_array($enum, ['suspended', ...])
        // will ALWAYS return false. Use isBlocked() which compares by enum case.
        if ($this->isBlocked($patient)) {
            $this->auditor->denied(
                request:   $request,
                eventType: AuditEventType::ConsentDenied,
                healthId:  $validated['health_id'],
                patientId: $patient->id,
                notes:     'BLOCKED_STATUS:' . ($patient->verification_status?->value ?? 'unknown'),
                facilityId: $facilityId,
            );
            return response()->json([
                'status'     => 'rejected',
                'error_code' => 'HEALTH_ID_SUSPENDED',
                'message'    => __('api.consent_blocked_status'),
            ], 403);
        }

        // ── 3. Create consent request (always 'pending' — patient must approve) ─
        $consentRequest = ConsentRequest::create([
            'patient_id'             => $patient->id,
            'requesting_facility_id' => $facilityId,
            'requesting_user_id'     => $actorId,
            'purpose'                => $validated['purpose'],
            'requested_scope'        => $validated['requested_scope'],
            'duration_minutes'       => $validated['expires_in_days']
                ? $validated['expires_in_days'] * 1440
                : 1440,  // default 24h if not specified
            'status'                 => 'pending',  // never auto-granted
        ]);

        // ── 4. Audit ──────────────────────────────────────────────────────────
        $this->auditor->record(
            request:    $request,
            eventType:  AuditEventType::ConsentGranted,
            result:     'pending',
            healthId:   $patient->health_id,
            patientId:  $patient->id,
            facilityId: $facilityId,
            purpose:    $validated['purpose'],
            notes:      'Consent request created — awaiting patient approval. consent_id=' . $consentRequest->id,
        );

        return response()->json([
            'status'         => 'success',
            'consent_id'     => $consentRequest->id,
            'consent_status' => 'pending',
            'message'        => __('api.consent_request_sent_patient'),
        ], 200);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Enum-safe blocked status check.
     * Covers: suspended, deceased, entered_in_error, merged, erasure_pending, expired.
     */
    private function isBlocked(Patient $patient): bool
    {
        return ($patient->verification_status?->isBlocked() ?? false)
            || ($patient->identity_status?->isBlocked() ?? false);
    }
}
