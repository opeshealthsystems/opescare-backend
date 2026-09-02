<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TrustBadgeVerification — Trust & Compliance Layer
 * A verification run against a badge's criteria for a holder.
 */
class TrustBadgeVerification extends Model
{
    use HasUuids;

    protected $fillable = [
        'trust_badge_id', 'trust_badge_assignment_id',
        'holder_type', 'holder_id',
        'status', 'criteria_results', 'verified_by', 'verified_at',
    ];

    protected $casts = ['criteria_results' => 'array', 'verified_at' => 'datetime'];

    public function trustBadge(): BelongsTo { return $this->belongsTo(TrustBadge::class); }

    public function hasPassed(): bool { return $this->status === 'passed'; }
}
