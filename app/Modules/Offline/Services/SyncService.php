<?php

namespace App\Modules\Offline\Services;

use App\Models\AuditEvent;
use App\Models\LocalCachePolicy;
use App\Models\OfflineAuditEvent;
use App\Models\OfflineQueue;
use App\Models\SyncConflict;
use App\Models\SyncJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SyncService
{
    private const ALLOWED_SCOPES = [
        'demographics',
        'appointments',
        'medications',
        'allergies',
        'emergency_profile',
    ];

    public function createLocalCachePolicy(array $data, ?string $actorId = null): LocalCachePolicy
    {
        $scopes = array_values($data['allowed_scopes'] ?? []);
        $invalid = array_diff($scopes, self::ALLOWED_SCOPES);

        if ($invalid !== []) {
            throw new RuntimeException('OFFLINE_SCOPE_NOT_ALLOWED');
        }

        if (($data['emergency_access'] ?? false) && $scopes !== ['emergency_profile']) {
            throw new RuntimeException('OFFLINE_EMERGENCY_SCOPE_LIMIT_REQUIRED');
        }

        $policy = LocalCachePolicy::create([
            'patient_id' => $data['patient_id'],
            'facility_id' => $data['facility_id'] ?? null,
            'device_id' => $data['device_id'],
            'allowed_scopes' => $scopes,
            'encryption_required' => true,
            'encryption_policy' => 'AES-256-GCM local database encryption required',
            'emergency_access' => $data['emergency_access'] ?? false,
            'review_required' => $data['emergency_access'] ?? false,
            'status' => 'active',
            'expires_at' => $data['expires_at'] ?? Carbon::now()->addHours(($data['emergency_access'] ?? false) ? 6 : 24),
            'created_by' => $actorId,
        ]);

        $this->offlineAudit(null, $policy, 'policy_created', ['scopes' => $scopes]);
        $this->auditPolicy($policy, ($policy->emergency_access ? 'emergency_policy_create' : 'policy_create'), $actorId);

        return $policy;
    }

    public function queueEncryptedPayload(LocalCachePolicy $policy, array $payload, ?string $actorId = null): OfflineQueue
    {
        if ($policy->status !== 'active' || ($policy->expires_at && $policy->expires_at->isPast())) {
            throw new RuntimeException('OFFLINE_POLICY_NOT_ACTIVE');
        }

        $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
        $hash = hash('sha256', $policy->id.'|'.$encoded);

        return DB::transaction(function () use ($policy, $encoded, $hash, $actorId) {
            $queue = OfflineQueue::firstOrCreate(
                ['local_cache_policy_id' => $policy->id, 'payload_hash' => $hash],
                [
                    'patient_id' => $policy->patient_id,
                    'facility_id' => $policy->facility_id,
                    'device_id' => $policy->device_id,
                    'scopes' => $policy->allowed_scopes,
                    'encrypted_payload' => Crypt::encryptString($encoded),
                    'status' => 'queued',
                    'retry_count' => 0,
                    'created_by' => $actorId,
                ]
            );

            SyncJob::firstOrCreate(
                ['offline_queue_id' => $queue->id],
                ['status' => 'pending', 'attempts' => 0]
            );

            if ($queue->wasRecentlyCreated) {
                $this->offlineAudit($queue, $policy, 'payload_queued', ['payload_hash' => $hash]);
                $this->auditQueue($queue, 'queue', $actorId, null, $queue->toArray());
            }

            return $queue;
        });
    }

    public function markSyncFailed(OfflineQueue $queue, string $error, ?string $actorId = null): OfflineQueue
    {
        $before = $queue->toArray();

        $queue->forceFill([
            'status' => 'retry_pending',
            'retry_count' => $queue->retry_count + 1,
            'last_error' => $error,
            'next_retry_at' => Carbon::now()->addMinutes(15),
        ])->save();

        SyncJob::where('offline_queue_id', $queue->id)->update([
            'status' => 'retry_pending',
            'attempts' => DB::raw('attempts + 1'),
            'last_attempted_at' => Carbon::now(),
        ]);

        $this->offlineAudit($queue->fresh(), null, 'sync_failed', ['error' => $error]);
        $this->auditQueue($queue->fresh(), 'retry', $actorId, $before, $queue->fresh()->toArray());

        return $queue->fresh();
    }

    public function detectConflict(OfflineQueue $queue, string $conflictType, ?string $actorId = null): SyncConflict
    {
        $conflict = SyncConflict::create([
            'offline_queue_id' => $queue->id,
            'conflict_type' => $conflictType,
            'status' => 'open',
        ]);

        $queue->forceFill(['status' => 'conflict'])->save();

        $this->offlineAudit($queue->fresh(), null, 'conflict_detected', ['conflict_type' => $conflictType]);
        $this->auditConflict($conflict, 'detect', $actorId);

        return $conflict;
    }

    public function resolveConflict(SyncConflict $conflict, string $strategy, ?string $actorId = null): SyncConflict
    {
        $conflict->forceFill([
            'status' => 'resolved',
            'resolution_strategy' => $strategy,
            'resolved_by' => $actorId,
            'resolved_at' => Carbon::now(),
        ])->save();

        OfflineQueue::whereKey($conflict->offline_queue_id)->update(['status' => 'queued']);

        $this->offlineAudit(OfflineQueue::find($conflict->offline_queue_id), null, 'conflict_resolved', ['strategy' => $strategy]);
        $this->auditConflict($conflict->fresh(), 'resolve', $actorId);

        return $conflict->fresh();
    }

    private function offlineAudit(?OfflineQueue $queue, ?LocalCachePolicy $policy, string $eventType, array $metadata = []): void
    {
        OfflineAuditEvent::create([
            'offline_queue_id' => $queue?->id,
            'local_cache_policy_id' => $policy?->id ?? $queue?->local_cache_policy_id,
            'patient_id' => $policy?->patient_id ?? $queue?->patient_id,
            'device_id' => $policy?->device_id ?? $queue?->device_id,
            'event_type' => $eventType,
            'metadata' => $metadata,
            'created_at' => Carbon::now(),
        ]);
    }

    private function auditPolicy(LocalCachePolicy $policy, string $action, ?string $actorId): void
    {
        AuditEvent::create([
            'actor_id' => $actorId,
            'facility_id' => $policy->facility_id,
            'patient_id' => $policy->patient_id,
            'action_type' => $action,
            'resource_type' => 'local_cache_policy',
            'resource_id' => $policy->id,
            'emergency_override' => $policy->emergency_access,
            'source_system' => 'opescare',
            'after_state' => $policy->toArray(),
            'created_at' => Carbon::now(),
        ]);
    }

    private function auditQueue(OfflineQueue $queue, string $action, ?string $actorId, ?array $before, ?array $after): void
    {
        AuditEvent::create([
            'actor_id' => $actorId,
            'facility_id' => $queue->facility_id,
            'patient_id' => $queue->patient_id,
            'action_type' => $action,
            'resource_type' => 'offline_queue',
            'resource_id' => $queue->id,
            'source_system' => 'opescare',
            'before_state' => $before,
            'after_state' => $after,
            'created_at' => Carbon::now(),
        ]);
    }

    private function auditConflict(SyncConflict $conflict, string $action, ?string $actorId): void
    {
        AuditEvent::create([
            'actor_id' => $actorId,
            'action_type' => $action,
            'resource_type' => 'sync_conflict',
            'resource_id' => $conflict->id,
            'source_system' => 'opescare',
            'after_state' => $conflict->toArray(),
            'created_at' => Carbon::now(),
        ]);
    }
}
