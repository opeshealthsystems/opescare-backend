<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * RolePermissionMatrix — Governance-layer record of what each role is allowed or blocked from doing.
 *
 * This model is a documentation/governance layer that supplements the runtime
 * permission system (Spatie). It captures the audited, reviewed permission
 * matrix for each role so that permission decisions are traceable.
 *
 * A permission_key in is_explicitly_blocked = true must NEVER be granted
 * to that role, even by super_admin, without a formal review record.
 */
class RolePermissionMatrix extends Model
{
    use HasUuids;

    protected $fillable = [
        'role_name', 'permission_family', 'permission_key',
        'is_allowed', 'is_explicitly_blocked',
        'rationale', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'is_allowed'            => 'boolean',
        'is_explicitly_blocked' => 'boolean',
        'reviewed_at'           => 'datetime',
    ];

    // ── Helpers ────────────────────────────────────────────────────────────

    public static function isBlocked(string $role, string $permissionKey): bool
    {
        return static::where('role_name', $role)
            ->where('permission_key', $permissionKey)
            ->where('is_explicitly_blocked', true)
            ->exists();
    }

    public function markReviewed(string $reviewedBy): void
    {
        $this->update(['reviewed_by' => $reviewedBy, 'reviewed_at' => now()]);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeForRole($query, string $role) { return $query->where('role_name', $role); }
    public function scopeExplicitlyBlocked($query) { return $query->where('is_explicitly_blocked', true); }
    public function scopeNeedsReview($query) { return $query->whereNull('reviewed_at'); }
}
