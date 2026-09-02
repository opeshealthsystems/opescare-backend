<?php

namespace App\Http\Controllers\Api\V1\Connect;

use App\Enums\AuditEventType;
use App\Enums\MedicalIdErrorCode;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\Identity\HealthIdAuditLogger;
use App\Services\Identity\HealthIdGeneratorService;
use App\Services\Identity\QrTokenService;
use Illuminate\Http\Request;

/**
 * Health ID Verification — B2B Connect API
 *
 * Used by external HIS, pharmacies, labs and insurance systems to verify
 * a patient Health ID or a QR scan before requesting consent.
 *
 * Both endpoints are audited via HealthIdAuditLogger per ISO 27799 §8.4.
 *
 * Security posture:
 * - All status comparisons use typed enum instances (not raw strings) to
 *   prevent silent false-negatives introduced by Wave 4 enum casting.
 * - facility_id is resolved exclusively from the verified bearer-token
 *   attributes set by VerifyBearerToken middleware — never from request input.
 * - The private logAccessEvent() pattern has been fully retired. All audit
 *   writes go through HealthIdAuditLogger for consistent field mapping and
 *   structured log emission.
 */
class MedicalIdVerificationController extends Controller
{
    public function __construct(
        private readonly HealthIdGeneratorService $healthIdService,
        private readonly QrTokenService           $qrService,
        private readonly HealthIdAuditLogger      $auditor,
    ) {}

    // =========================================================================
    // POST /api/v1/connect/medical-ids/verify
    // =========================================================================

    public function verifyHealthId(Request $request)
    {
        $validated = $request->validate([
            'health_id'                        => 'required|string|max:30',
            'purpose'                          => 'required|string|max:200',
            'requesting_user.external_user_id' => 'nullable|string|max:100',
            'requesting_user.role'             => 'nullable|string|max:80',
        ]);

        $healthId   = strtoupper(trim($validated['health_id']));
        $purpose    = $validated['purpose'];
        $facilityId = $request->attributes->get('facility_id');

        // ── 1. Format + checksum validation ──────────────────────────────────
        if (! $this->healthIdService->isValid($healthId)) {
            $this->auditor->denied(
                request:   $request,
                eventType: AuditEventType::MedicalIdAccessDenied,
                healthId:  $healthId,
                patientId: null,
                notes:     'INVALID_HEALTH_ID_FORMAT',
                facilityId: $facilityId,
            );
            return response()->json([
                'status'     => 'rejected',
                'error_code' => MedicalIdErrorCode::INVALID_HEALTH_ID_FORMAT->value,
                'message'    => __('api.health_id_unverified_retry'),
            ], 400);
        }

        // ── 2. Patient lookup ─────────────────────────────────────────────────
        $patient = Patient::where('health_id', $healthId)->first();

        if (! $patient) {
            $this->auditor->denied(
                request:   $request,
                eventType: AuditEventType::MedicalIdAccessDenied,
                healthId:  $healthId,
                patientId: null,
                notes:     'HEALTH_ID_NOT_FOUND',
                facilityId: $facilityId,
            );
            return response()->json([
                'status'     => 'rejected',
                'error_code' => MedicalIdErrorCode::HEALTH_ID_NOT_FOUND->value,
                'message'    => __('api.health_id_unverified_retry'),
            ], 404);
        }

        // ── 3. Status gate — enum-safe comparison ─────────────────────────────
        // IMPORTANT: $patient->verification_status and identity_status are now
        // typed VerificationStatus/IdentityStatus enum objects (Wave 4 casts).
        // Raw string comparison (=== 'suspended') will always be FALSE.
        // Always use ->isBlocked() or compare against enum instances.
        if ($this->isBlocked($patient)) {
            $statusValue = $patient->verification_status?->value ?? 'unknown';
            $this->auditor->denied(
                request:   $request,
                eventType: AuditEventType::MedicalIdAccessDenied,
                healthId:  $healthId,
                patientId: $patient->id,
                notes:     'BLOCKED_STATUS:' . $statusValue,
                facilityId: $facilityId,
            );
            return response()->json([
                'status'     => 'rejected',
                'error_code' => $this->statusErrorCode($patient)->value,
                'message'    => __('api.access_restricted_status', ['status' => $statusValue]),
            ], 403);
        }

        // ── 4. Audit success ──────────────────────────────────────────────────
        $this->auditor->record(
            request:    $request,
            eventType:  AuditEventType::ExternalSystemVerifiedHealthId,
            result:     'success',
            healthId:   $healthId,
            patientId:  $patient->id,
            facilityId: $facilityId,
            purpose:    $purpose,
        );

        // ── 5. Safe identity preview — never return full PII ─────────────────
        return response()->json([
            'status'              => 'valid',
            'verification_status' => $patient->verification_status?->value ?? 'provisional',
            'patient_preview'     => [
                'display_name'  => $this->maskName($patient),
                'sex'           => $patient->sex,
                'year_of_birth' => $patient->date_of_birth?->year,
                'health_id'     => $patient->health_id,
            ],
            'next_action' => 'request_consent',
        ]);
    }

    // =========================================================================
    // POST /api/v1/connect/medical-ids/verify-qr
    // =========================================================================

    public function verifyQr(Request $request)
    {
        $validated = $request->validate([
            'qr_token' => 'required|string',
            'purpose'  => 'required|string|max:200',
        ]);

        $facilityId    = $request->attributes->get('facility_id');
        $qrTokenRecord = $this->qrService->verifyToken($validated['qr_token']);

        if (! $qrTokenRecord) {
            return response()->json([
                'status'     => 'rejected',
                'error_code' => MedicalIdErrorCode::QR_TOKEN_INVALID->value,
                'message'    => 'This QR token is invalid, expired, or revoked.',
            ], 400);
        }

        $patient = $qrTokenRecord->patient;

        // Enum-safe status check — covers suspended, deceased, merged, erasure_pending, expired
        if ($this->isBlocked($patient)) {
            $statusValue = $patient->verification_status?->value ?? 'unknown';
            $this->auditor->denied(
                request:   $request,
                eventType: AuditEventType::MedicalIdAccessDenied,
                healthId:  $patient->health_id,
                patientId: $patient->id,
                notes:     'QR_BLOCKED_STATUS:' . $statusValue,
                facilityId: $facilityId,
            );
            return response()->json([
                'status'     => 'rejected',
                'error_code' => $this->statusErrorCode($patient)->value,
                'message'    => __('api.access_restricted_status', ['status' => $statusValue]),
            ], 403);
        }

        $this->auditor->record(
            request:    $request,
            eventType:  AuditEventType::VerifyQr,
            result:     'success',
            healthId:   $patient->health_id,
            patientId:  $patient->id,
            facilityId: $facilityId,
            purpose:    $validated['purpose'],
        );

        return response()->json([
            'status'              => 'valid',
            'verification_status' => $patient->verification_status?->value ?? 'provisional',
            'patient_preview'     => [
                'display_name'  => $this->maskName($patient),
                'sex'           => $patient->sex,
                'year_of_birth' => $patient->date_of_birth?->year,
                'health_id'     => $patient->health_id,
            ],
            'next_action' => 'request_consent',
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Returns true if the patient's verification OR identity status is
     * in any blocked state (suspended, deceased, entered_in_error, merged,
     * erasure_pending, expired).
     *
     * Uses the typed isBlocked() method from the enum (Wave 4) to avoid
     * broken raw-string comparisons against enum objects.
     */
    private function isBlocked(Patient $patient): bool
    {
        return ($patient->verification_status?->isBlocked() ?? false)
            || ($patient->identity_status?->isBlocked() ?? false);
    }

    /**
     * Map the patient's verification status to an appropriate MedicalIdErrorCode.
     */
    private function statusErrorCode(Patient $patient): MedicalIdErrorCode
    {
        return match ($patient->verification_status) {
            VerificationStatus::Deceased      => MedicalIdErrorCode::HEALTH_ID_DECEASED,
            VerificationStatus::EnteredInError => MedicalIdErrorCode::HEALTH_ID_ENTERED_IN_ERROR,
            default                           => MedicalIdErrorCode::HEALTH_ID_SUSPENDED,
        };
    }

    /**
     * Return "FirstName L." — first name + first character of last name.
     * Never expose full names through the B2B verification API.
     */
    private function maskName(Patient $patient): string
    {
        $firstName = $patient->first_name ?? '';
        $lastName  = $patient->last_name  ?? '';

        if (empty($firstName) && empty($lastName)) {
            return 'Unknown Patient';
        }

        if (empty($lastName)) {
            return $firstName;
        }

        return $firstName . ' ' . mb_substr($lastName, 0, 1) . '.';
    }
}
