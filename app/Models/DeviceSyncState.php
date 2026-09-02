<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * DeviceSyncState — Offline Sync (Module 24)
 *
 * Tracks the per-device, per-user, per-resource-type sync cursor.
 * Used to determine what needs to be pushed or pulled on the next sync.
 */
class DeviceSyncState extends Model
{
    use HasUuids;

    protected $fillable = [
        'device_id',
        'user_id',
        'resource_type',
        'last_synced_at',
        'last_sync_status',   // success|failed|conflict
        'pending_push_count',
        'pending_pull_count',
    ];

    protected $casts = [
        'last_synced_at'    => 'datetime',
        'pending_push_count' => 'integer',
        'pending_pull_count' => 'integer',
    ];

    public function hasPendingWork(): bool
    {
        return $this->pending_push_count > 0 || $this->pending_pull_count > 0;
    }

    public function markSynced(): void
    {
        $this->update([
            'last_synced_at'     => now(),
            'last_sync_status'   => 'success',
            'pending_push_count' => 0,
            'pending_pull_count' => 0,
        ]);
    }

    public function scopeForDevice($query, string $deviceId, string $userId)
    {
        return $query->where('device_id', $deviceId)->where('user_id', $userId);
    }

    public static function upsertFor(string $deviceId, string $userId, string $resourceType, array $data = []): self
    {
        return static::updateOrCreate(
            ['device_id' => $deviceId, 'user_id' => $userId, 'resource_type' => $resourceType],
            $data
        );
    }
}
