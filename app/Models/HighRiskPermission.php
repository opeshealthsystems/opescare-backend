<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * HighRiskPermission — Registry of permissions classified as high-risk.
 *
 * High-risk permissions (e.g. patients.view_full, billing.refund, emergency_access.use)
 * require explicit grant, a stated reason, optional approval workflow,
 * a created audit event, and periodic access review.
 *
 * Required per OPESCARE_STRATEGIC_MATURITY §9.6.
 */
class HighRiskPermission extends Model
{
    use HasUuids;

    protected $fillable = [
        'permission_key', 'description',
        'requires_explicit_grant', 'requires_reason',
        'requires_approval_workflow', 'requires_periodic_review',
        'review_interval_days', 'creates_audit_event', 'is_active',
    ];

    protected $casts = [
        'requires_explicit_grant'    => 'boolean',
        'requires_reason'            => 'boolean',
        'requires_approval_workflow' => 'boolean',
        'requires_periodic_review'   => 'boolean',
        'creates_audit_event'        => 'boolean',
        'is_active'                  => 'boolean',
        'review_interval_days'       => 'integer',
    ];

    public function scopeActive($query) { return $query->where('is_active', true); }

    public static function isHighRisk(string $permissionKey): bool
    {
        return static::where('permission_key', $permissionKey)->where('is_active', true)->exists();
    }
}
