<?php

namespace App\Modules\SecurityOperations\Services;

use App\Models\AuditEvent;
use App\Models\SuspiciousAccessFlag;
use Illuminate\Support\Facades\DB;

/**
 * SuspiciousAccessDetectionService — Heuristic detection of anomalous access patterns.
 *
 * Detects:
 *  - High-volume patient record access within a short window
 *  - Emergency access not followed by clinical documentation
 *
 * Findings are written to SuspiciousAccessFlag for human review.
 * No automated blocking occurs without explicit configuration.
 *
 * Finder adaptations (docs/PLATFORM_IMPORTS.md, group D):
 *  - high-volume access reads `access_logs` (the Finder writes one patient-scoped
 *    row per chart/record read through AccessLogService), counting DISTINCT
 *    patients, instead of audit_events with resource_type = patient.
 *  - undocumented emergency access reads the break-glass table
 *    `emergency_access_events` instead of audit_events 'emergency_profile'.
 *  - scan() runs both over the window for every actor, and never raises a
 *    second OPEN flag of the same type for the same user (re-runs are safe).
 *  - queries use the query builder, so demo isolation never hides real rows.
 *  - detection never edits or deletes a log row; it only adds flags.
 *  - the platform does not schedule this service, so neither does the Finder;
 *    it runs from the desk's "Run detection" button.
 */
class SuspiciousAccessDetectionService
{
    public const HIGH_VOLUME_THRESHOLD = 50; // distinct patients in 1 hour
    public const HIGH_VOLUME_WINDOW_MINUTES = 60;

    public function detectHighVolumeAccess(string $userId, ?string $facilityId = null): ?SuspiciousAccessFlag
    {
        $windowStart = now()->subMinutes(self::HIGH_VOLUME_WINDOW_MINUTES);

        $count = (int) DB::table('access_logs')
            ->where('actor_id', $userId)
            ->when($facilityId, fn ($q) => $q->where('facility_id', $facilityId))
            ->whereNotNull('patient_id')
            ->where('created_at', '>=', $windowStart)
            ->distinct()
            ->count('patient_id');

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
        $emergencyEvent = DB::table('emergency_access_events')
            ->where('provider_id', $userId)
            ->where('patient_id', $patientId)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        $hasDocumentation = DB::table('audit_events')
            ->where('actor_id', $userId)
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

    /**
     * Run every detector over the recent window. Returns the flags raised or still open.
     *
     * @return list<SuspiciousAccessFlag>
     */
    public function scan(): array
    {
        $flags = [];

        $actors = DB::table('access_logs')
            ->whereNotNull('patient_id')
            ->where('created_at', '>=', now()->subMinutes(self::HIGH_VOLUME_WINDOW_MINUTES))
            ->distinct()
            ->pluck('actor_id');

        foreach ($actors as $actorId) {
            if ($flag = $this->detectHighVolumeAccess((string) $actorId)) {
                $flags[] = $flag;
            }
        }

        $pairs = DB::table('emergency_access_events')
            ->whereNotNull('provider_id')
            ->whereNotNull('patient_id')
            ->where('created_at', '>=', now()->subHours(24))
            ->select('provider_id', 'patient_id')
            ->distinct()
            ->get();

        foreach ($pairs as $pair) {
            if ($flag = $this->detectUndocumentedEmergencyAccess((string) $pair->provider_id, (string) $pair->patient_id)) {
                $flags[] = $flag;
            }
        }

        return $flags;
    }

    private function flag(string $userId, ?string $facilityId, string $type, array $context): SuspiciousAccessFlag
    {
        $open = SuspiciousAccessFlag::where('user_id', $userId)
            ->where('flag_type', $type)
            ->where('status', 'open')
            ->get()
            ->first(fn (SuspiciousAccessFlag $f) => ! isset($context['patient_id'])
                || ($f->evidence['patient_id'] ?? null) === $context['patient_id']);

        if ($open) {
            return $open;
        }

        $flag = SuspiciousAccessFlag::create([
            'user_id'   => $userId,
            'flag_type' => $type,
            'severity'  => $this->severityFor($type),
            'evidence'  => array_merge($context, array_filter(['facility_id' => $facilityId])),
            'status'    => 'open',
        ]);

        AuditEvent::create([
            'action_type'   => 'create',
            'resource_type' => 'suspicious_access_flag',
            'resource_id'   => $flag->id,
            'reason'        => 'Suspicious access flagged: ' . $type,
        ]);

        return $flag;
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
