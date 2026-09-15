<?php

namespace App\Modules\Broadcasts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Broadcast — System or facility-wide message pushed to a set of recipients.
 *
 * Lifecycle: draft → published → (cancelled | expired)
 *
 * When requires_acknowledgement = true, every targeted user must acknowledge
 * via POST /v1/broadcasts/{id}/acknowledge, which creates a BroadcastAcknowledgement.
 *
 * SECURITY: created_by is always sourced from authenticated middleware context,
 *           never from request headers.
 */
class Broadcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'broadcast_type',
        'title',
        'body',
        'target_type',
        'target_ids_json',
        'priority',
        'language',
        'requires_acknowledgement',
        'status',
        'publish_at',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'requires_acknowledgement' => 'boolean',
        'publish_at'               => 'datetime',
        'expires_at'               => 'datetime',
        'created_at'               => 'datetime',
        'updated_at'               => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(BroadcastAcknowledgement::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function getTargetIdsAttribute(): array
    {
        return json_decode($this->target_ids_json ?? '[]', true) ?? [];
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function acknowledgementCount(): int
    {
        return $this->acknowledgements()->count();
    }
}
