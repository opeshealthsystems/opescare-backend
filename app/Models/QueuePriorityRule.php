<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QueuePriorityRule — Module 6 (Queue & Patient Flow)
 *
 * Defines facility-level rules that boost queue priority for specific
 * patient groups (elderly, pregnant, emergency, disability, VIP).
 * Rules are evaluated at ticket creation time.
 */
class QueuePriorityRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id',
        'queue_id',
        'rule_name',
        'rule_type',
        'conditions',
        'priority_boost',
        'requires_confirmation',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'conditions'           => 'array',
        'requires_confirmation' => 'boolean',
        'is_active'            => 'boolean',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(FacilityQueue::class, 'queue_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForFacility($query, string $facilityId)
    {
        return $query->where('facility_id', $facilityId);
    }
}
