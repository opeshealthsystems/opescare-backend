<?php

namespace App\Modules\Offline\Services;

use App\Models\SyncConflict;
use App\Models\AuditEvent;

/**
 * ConflictResolutionService — Manages offline sync conflict resolution.
 *
 * SAFETY RULE: Conflicts must NEVER be resolved silently or automatically
 * for clinical records (encounters, prescriptions, lab results, vital signs).
 * Automatic resolution is only allowed for non-clinical metadata.
 *
 * Conflict resolution strategies:
 *  - server_wins:  Accept the server version (discard offline change)
 *  - client_wins:  Accept the offline version (overwrite server version)
 *  - manual_merge: Human reviews both versions and chooses/merges
 *
 * All resolutions are audited. Clinical conflicts must use manual_merge.
 */
class ConflictResolutionService
{
    private const CLINICAL_RESOURCE_TYPES = [
        'encounter', 'prescription', 'lab_result', 'vital_sign',
        'triage_record', 'clinical_note', 'medication_administration',
    ];

    /**
     * Resolve a conflict.
     *
     * @throws \DomainException if clinical record conflicts with auto strategy
     */
    public function resolve(
        string $conflictId,
        string $strategy, // server_wins|client_wins|manual_merge
        string $resolvedBy,
        array $mergedPayload = null
    ): SyncConflict {
        $conflict = SyncConflict::findOrFail($conflictId);

        if (
            in_array($conflict->resource_type, self::CLINICAL_RESOURCE_TYPES)
            && in_array($strategy, ['server_wins', 'client_wins'])
            && $mergedPayload === null
        ) {
            throw new \DomainException(
                "Clinical conflicts ({$conflict->resource_type}) require manual_merge strategy."
            );
        }

        $conflict->update([
            'resolution_strategy' => $strategy,
            'resolved_by'         => $resolvedBy,
            'resolved_at'         => now(),
            'merged_payload'      => $mergedPayload,
            'status'              => 'resolved',
        ]);

        AuditEvent::create([
            'actor_id'  => $resolvedBy,
            'action'    => 'sync_conflict.resolved',
            'module'    => 'offline',
            'metadata'  => [
                'conflict_id'   => $conflictId,
                'resource_type' => $conflict->resource_type,
                'strategy'      => $strategy,
            ],
        ]);

        return $conflict->fresh();
    }

    /** Returns all unresolved conflicts for a device. */
    public function getUnresolvedForDevice(string $deviceId): \Illuminate\Database\Eloquent\Collection
    {
        return SyncConflict::where('device_id', $deviceId)
            ->where('status', 'unresolved')
            ->orderByDesc('created_at')
            ->get();
    }

    /** Returns all clinical conflicts requiring human review. */
    public function getClinicalConflictsPendingReview(): \Illuminate\Database\Eloquent\Collection
    {
        return SyncConflict::whereIn('resource_type', self::CLINICAL_RESOURCE_TYPES)
            ->where('status', 'unresolved')
            ->orderByDesc('created_at')
            ->get();
    }
}
