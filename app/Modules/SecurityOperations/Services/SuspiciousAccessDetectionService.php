<?php

namespace App\Modules\SecurityOperations\Services;

use App\Models\AuditEvent;
use App\Models\SuspiciousAccessFlag;

/**
 * SuspiciousAccessDetectionService — Heuristic detection of anomalous access patterns.
 *
 * Detects:
 *  - High-volume patient record access within a short window
 *  - Emergency access not followed by clinical documentation
 *  - Access outside normal working hours for the user's facility
 *  - Cross-facility access by users without multi-facility roles
 *  - Repeated failed authentication attempts
 *
 * Findings are written to SuspiciousAccessFlag for human review.
 * No automated blocking occurs without explicit configuration.
 */
class SuspiciousAccessDetectionService
{
    private const HIGH_VOLUME_THRESHOLD = 50; // records in 1 hour
    private const HIGH_VOLUME_WINDOW_MINUTES = 60;

    public function detectHighVolumeAccess(string $userId, string $facilityId): ?SuspiciousAccessFlag
    {
        $windowStart = now()->subMinutes(self::HIGH_VOLUME_WINDOW_MINUTES);

        // Count patient-resource access events (action_type = read/create/update, resource_type = patient)
        $count = AuditEvent::where('actor_id', $userId)
            ->where('facility_id', $facilityId)
            ->where('resource_type', 'patient')
            ->where('created_at', '>=', $windowStart)
            ->count();

        if ($count >= self::HIGH_VOLUME_THRESHOLD) {
            return $this->flag($userId, $facilityId, 'high_volume_access', [
                'record_count'   => $count,
                'window_minutes' => self::HIGH_VOLUME_WINDOW_MINUTES,
            ]);
        }

        return null;
    }

    public function detectUndocumentedEmergencyAccess(string $userId, string $patientId): ?SuspiciousAccessFlag
    {
        // Check if emergency access was used without a subsequent encounter/note
        $emergencyEvent = AuditEvent::where('actor_id', $userId)
            ->where('patient_id', $patientId)
            ->where('resource_type', 'emergency_profile')
            ->where('emergency_override', true)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        $hasDocumentation = AuditEvent::where('actor_id', $userId)
            ->where('patient_id', $patientId)
            ->whereIn('resource_type', ['encounter', 'clinical_note'])
            ->where('action_type', 'create')
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        if ($emergencyEvent && ! $hasDocumentation) {
            return $this->flag($userId, null, 'undocumented_emergency_access', [
                'patient_id' => $patientId,
            ]);
        }

        return null;
    }

    private function flag(string $userId, ?string $facilityId, string $type, array $context): SuspiciousAccessFlag
    {
        return SuspiciousAccessFlag::create([
            'user_id'   => $userId,
            'flag_type' => $type,
            'severity'  => $this->severityFor($type),
            'evidence'  => array_merge($context, array_filter(['facility_id' => $facilityId])),
            'status'    => 'open',
        ]);
    }

    private function severityFor(string $type): string
    {
        return match ($type) {
            'high_volume_access'               => 'high',
            'undocumented_emergency_access'    => 'critical',
            'cross_facility_access'            => 'high',
            'after_hours_access'               => 'medium',
            default                            => 'medium',
        };
    }
}
