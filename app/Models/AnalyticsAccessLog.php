<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AnalyticsAccessLog — Module 19 (Analytics & Reporting)
 *
 * Immutable append-only log of analytics resource access.
 * Tracks who viewed, exported, or downloaded analytics data.
 *
 * Security constraint: Analytics access must be auditable.
 * Facility-scoped analytics must verify facility access before logging.
 */
class AnalyticsAccessLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'actor_id',
        'actor_role',
        'facility_id',
        'resource_type',    // dashboard|report|metric|export
        'resource_id',
        'action',           // viewed|exported|downloaded|filtered
        'parameters',
        'occurred_at',
    ];

    protected $casts = [
        'parameters'  => 'array',
        'occurred_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function facility(): BelongsTo
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }

    // ── Factory ───────────────────────────────────────────────────────────────

    /**
     * Append-only log entry. Never update or delete analytics access logs.
     */
    public static function record(
        string $actorId,
        ?string $actorRole,
        ?string $facilityId,
        string $resourceType,
        ?string $resourceId,
        string $action,
        ?array $parameters = null
    ): self {
        return static::create([
            'actor_id'      => $actorId,
            'actor_role'    => $actorRole,
            'facility_id'   => $facilityId,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'action'        => $action,
            'parameters'    => $parameters,
            'occurred_at'   => now(),
        ]);
    }
}
