<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TrustBadgeAssignment — Trust & Compliance Layer
 * Grant of a TrustBadge to a Facility, Organization, or DeveloperApp.
 */
class TrustBadgeAssignment extends Model
{
    use HasUuids;

    protected $fillable = [
        'trust_badge_id', 'holder_type', 'holder_id',
        'status', 'granted_at', 'expires_at', 'granted_by',
    ];

    protected $casts = ['granted_at' => 'datetime', 'expires_at' => 'datetime'];

    public function trustBadge(): BelongsTo { return $this->belongsTo(TrustBadge::class); }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function revoke(): void { $this->update(['status' => 'revoked']); }

    public function scopeActive($query) { return $query->where('status', 'active'); }
}
