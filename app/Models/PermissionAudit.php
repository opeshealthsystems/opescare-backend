<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * PermissionAudit — Append-only audit log for permission grant/revoke/review events.
 *
 * Every grant or revoke of a high-risk permission must create a record here.
 * This model is append-only — update() and delete() are intentionally blocked.
 */
class PermissionAudit extends Model
{
    use HasUuids;

    public const UPDATED_AT = null; // no updated_at column

    protected $fillable = [
        'actor_id', 'target_user_id', 'action',
        'role_name', 'permission_key', 'facility_id',
        'reason', 'metadata', 'occurred_at',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'occurred_at' => 'datetime',
    ];

    public static function record(
        string $actorId,
        string $targetUserId,
        string $action,
        array $extra = []
    ): self {
        return static::create(array_merge([
            'actor_id'       => $actorId,
            'target_user_id' => $targetUserId,
            'action'         => $action,
            'occurred_at'    => now(),
        ], $extra));
    }

    /** @throws \LogicException always — append-only */
    public function update(array $a = [], array $o = []): bool
    {
        throw new \LogicException('PermissionAudit is append-only.');
    }

    /** @throws \LogicException always — append-only */
    public function delete(): ?bool
    {
        throw new \LogicException('PermissionAudit is append-only.');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeForUser($query, string $userId)
    {
        return $query->where('target_user_id', $userId);
    }

    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeForPermission($query, string $key)
    {
        return $query->where('permission_key', $key);
    }
}
