<?php

namespace App\Enums;

/**
 * Audit Event Taxonomy
 *
 * Centralised vocabulary for all audit event types written to:
 *   - medical_id_access_events.access_type
 *   - audit_events.action_type
 *   - application logs (Log::info / Log::warning)
 *
 * Why an enum:
 *   Using string literals directly across 15+ controllers means a typo
 *   like 'emergancy_access' silently creates an unmatchable event category.
 *   This enum makes every event name a compile-time constant, IDE-navigable,
 *   and grep-able by value.
 *
 * Usage:
 *   MedicalIdAccessEvent::create(['access_type' => AccessEventType::EmergencyAccess->value, …])
 *   Log::info(AuditEventType::HealthIdAliasResolved->value, […])
 *
 * Naming convention:
 *   PascalCase case name  →  snake_case string value (matches DB / log conventions)
 *
 * MINSANTE compliance: All cases marked [MINSANTE] must appear in monthly
 * reports and be individually justifiable upon inspection.
 */
enum AuditEventType: string
{
    // ── Health ID Lifecycle ──────────────────────────────────────────────────

    case HealthIdCreated             = 'health_id_created';
    case HealthIdAutoCreated         = 'health_id_auto_created';
    case HealthIdVerified            = 'health_id_verified';
    case HealthIdResolved            = 'health_id_resolved';
    case HealthIdAliasResolved       = 'health_id_alias_resolved';
    case HealthIdAutoCreateFailed    = 'health_id_auto_create_failed';
    case HealthIdCollision           = 'health_id_collision';
    case HealthIdStatusChanged       = 'health_id_status_changed';
    case HealthIdExpiryNotified      = 'health_id_expiry_notified';

    // ── QR Token ────────────────────────────────────────────────────────────

    case HealthIdQrScanned           = 'health_id_qr_scanned';
    case HealthIdQrTokenCreated      = 'health_id_qr_token_created';
    case HealthIdQrTokenRevoked      = 'health_id_qr_token_revoked';
    case HealthIdQrTokenExpired      = 'health_id_qr_token_expired';
    case TemporaryConsentQrCreated   = 'temporary_consent_qr_created';
    case TemporaryConsentQrUsed      = 'temporary_consent_qr_used';
    case TemporaryConsentQrExpired   = 'temporary_consent_qr_expired';

    // ── Verification ─────────────────────────────────────────────────────────

    case VerifyHealthId              = 'verify_health_id';
    case VerifyQr                    = 'verify_qr';
    case ExternalSystemVerifiedHealthId = 'external_system_verified_health_id';

    // ── Emergency Access [MINSANTE] ───────────────────────────────────────────

    case PullEmergencyProfile        = 'pull_emergency_profile';
    case EmergencyAccessGranted      = 'emergency_access_granted';
    case EmergencyAccessDenied       = 'emergency_access_denied';
    case EmergencyAccessPatientNotified = 'emergency_access_patient_notified';

    // ── Consent ──────────────────────────────────────────────────────────────

    case RequestConsent              = 'request_consent';
    case ConsentGranted              = 'consent_granted';
    case ConsentDenied               = 'consent_denied';
    case ConsentRevoked              = 'consent_revoked';

    // ── Identity & Profile ───────────────────────────────────────────────────

    case IdentityProfileUpdated      = 'identity_profile_updated';
    case PatientProfileUpdated       = 'patient_profile_updated';

    // ── Duplicate / Merge ─────────────────────────────────────────────────────

    case HealthIdDuplicateSuspected  = 'health_id_duplicate_suspected';
    case HealthIdMerged              = 'health_id_merged';
    case HealthIdUnmerged            = 'health_id_unmerged';

    // ── Data Subject Rights [MINSANTE] ────────────────────────────────────────

    case DataExport                  = 'data_export';
    case DataRectificationRequest    = 'data_rectification_request';
    case DataErasureRequest          = 'data_erasure_request';

    // ── Portal Actions ────────────────────────────────────────────────────────

    case PatientAccessLogView        = 'patient_access_log_view';
    case HealthIdCardDownloaded      = 'health_id_card_downloaded';
    case LostCardReported            = 'lost_card_reported';

    // ── API / Auth ────────────────────────────────────────────────────────────

    case ApiTokenIssued              = 'api_token_issued';
    case WidgetSessionCreated        = 'widget_session_created';
    case MedicalIdAccessDenied       = 'medical_id_access_denied';

    // ── Search / Lookup ───────────────────────────────────────────────────────

    case PatientSearch               = 'patient_search';
    case ReconciliationStarted       = 'reconciliation_started';
    case ReconciliationCompleted     = 'reconciliation_completed';

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Cases that must appear in the MINSANTE monthly compliance report.
     */
    public function isMinsanteReportable(): bool
    {
        return in_array($this, [
            self::PullEmergencyProfile,
            self::EmergencyAccessGranted,
            self::EmergencyAccessDenied,
            self::DataExport,
            self::DataRectificationRequest,
            self::DataErasureRequest,
            self::HealthIdMerged,
            self::ConsentRevoked,
        ], true);
    }

    /**
     * Severity level for monitoring alerts.
     * 'critical' → page on-call. 'high' → Slack alert. 'normal' → log only.
     */
    public function severity(): string
    {
        return match ($this) {
            self::EmergencyAccessGranted,
            self::DataErasureRequest,
            self::HealthIdMerged             => 'critical',

            self::EmergencyAccessDenied,
            self::DataRectificationRequest,
            self::MedicalIdAccessDenied,
            self::ConsentRevoked             => 'high',

            default                          => 'normal',
        };
    }
}
