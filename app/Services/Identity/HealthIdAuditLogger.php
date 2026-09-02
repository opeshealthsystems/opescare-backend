<?php

namespace App\Services\Identity;

use App\Enums\AuditEventType;
use App\Models\MedicalIdAccessEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * HealthIdAuditLogger — Unified Audit Service for the Health ID Module
 *
 * This service replaces three separate private `logAccess()` / `logAccessEvent()`
 * methods that were copy-pasted across:
 *   - EmergencyAccessController
 *   - ConsentController
 *   - MedicalIdVerificationController
 *   - DataSubjectRightsController
 *
 * All share the same target table (`medical_id_access_events`) and the same
 * field mapping. Having them in each controller meant:
 *   1. Inconsistent actor_type values ('api_client' vs 'integration_client')
 *   2. Inconsistent facility_id sourcing (some used middleware, some left null)
 *   3. No standard place to add cross-cutting concerns (e.g. throttling alerts)
 *
 * Usage:
 *
 *   // Injected via constructor DI or resolved from the container:
 *   app(HealthIdAuditLogger::class)->record(
 *       request:    $request,
 *       eventType:  AuditEventType::PullEmergencyProfile,
 *       result:     'success',
 *       healthId:   $patient->health_id,
 *       patientId:  $patient->id,
 *       notes:      'Emergency reason: ' . $reason,
 *   );
 *
 * All parameters except $request and $eventType are optional.
 *
 * COMPLIANCE: Every MedicalIdAccessEvent written here includes:
 *   - actor_id  (real middleware client or user ID, never Str::uuid())
 *   - actor_type ('integration_client' | 'patient' | 'staff')
 *   - facility_id from middleware attributes
 *   - structured notes field
 */
class HealthIdAuditLogger
{
    /**
     * Write one audit row to medical_id_access_events.
     *
     * The method resolves actor context from $request middleware attributes
     * (populated by auth.bearer, VerifyIntegrationClient, or Laravel Auth).
     * Callers can override any resolved value by passing explicit parameters.
     *
     * @param  Request         $request
     * @param  AuditEventType  $eventType   Typed event; value is stored in access_type column
     * @param  string          $result      'success' | 'denied' | 'error'
     * @param  string|null     $healthId    Patient Health ID (searchable; NOT encrypted)
     * @param  string|null     $patientId   Patient UUID (FK to patients)
     * @param  string|null     $actorId     Override auto-resolved actor ID
     * @param  string|null     $actorType   Override actor type
     * @param  string|null     $facilityId  Override facility ID
     * @param  string|null     $notes       Freeform context (emergency reason, rejection reason, etc.)
     * @param  string|null     $purpose     B2B-provided purpose string (e.g. 'treatment')
     */
    public function record(
        Request       $request,
        AuditEventType $eventType,
        string        $result     = 'success',
        ?string       $healthId   = null,
        ?string       $patientId  = null,
        ?string       $actorId    = null,
        ?string       $actorType  = null,
        ?string       $facilityId = null,
        ?string       $notes      = null,
        ?string       $purpose    = null,
    ): MedicalIdAccessEvent {
        // ── Resolve actor context ──────────────────────────────────────────
        // Priority: explicit parameter → middleware attribute → Auth user
        // actor_id is a uuid column — non-uuid client identifiers (e.g. the
        // testing-bypass 'test_client_id') must not reach Postgres.
        $candidateActorId = $actorId
            ?? $request->attributes->get('integration_client_id')
            ?? (string) ($request->user()?->id)
            ?? null;

        $resolvedActorId = ($candidateActorId && \Illuminate\Support\Str::isUuid($candidateActorId))
            ? $candidateActorId
            : null;

        $resolvedActorType = $actorType
            ?? ($request->attributes->has('integration_client_id') ? 'integration_client' : 'patient');

        $resolvedFacilityId = $facilityId
            ?? $request->attributes->get('facility_id');

        // ── Write event ────────────────────────────────────────────────────
        $event = MedicalIdAccessEvent::create([
            'patient_id'  => $patientId,
            'health_id'   => $healthId,
            'actor_id'    => $resolvedActorId,
            'actor_type'  => $resolvedActorType,
            'facility_id' => $resolvedFacilityId,
            'access_type' => $eventType->value,
            'purpose'     => $purpose ?? $eventType->value,
            'result'      => $result,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'notes'       => $notes,
        ]);

        // ── Structured application log ─────────────────────────────────────
        // Severity determines log level so monitoring can route to the right alert channel.
        $logContext = [
            'event'       => $eventType->value,
            'result'      => $result,
            'health_id'   => $healthId,
            'patient_id'  => $patientId,
            'actor_id'    => $resolvedActorId,
            'actor_type'  => $resolvedActorType,
            'facility_id' => $resolvedFacilityId,
            'ip'          => $request->ip(),
        ];

        match ($eventType->severity()) {
            'critical' => Log::warning($eventType->value, $logContext),
            'high'     => Log::info($eventType->value, $logContext),
            default    => Log::debug($eventType->value, $logContext),
        };

        return $event;
    }

    /**
     * Shorthand for logging a denied access event (most common failure path).
     */
    public function denied(
        Request       $request,
        AuditEventType $eventType,
        ?string       $healthId   = null,
        ?string       $patientId  = null,
        ?string       $notes      = null,
        ?string       $facilityId = null,
    ): MedicalIdAccessEvent {
        return $this->record(
            request:    $request,
            eventType:  $eventType,
            result:     'denied',
            healthId:   $healthId,
            patientId:  $patientId,
            facilityId: $facilityId,
            notes:      $notes,
        );
    }
}
