<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TrustBadgeCriteria — Trust & Compliance Layer
 * A single criterion that must be met to earn a TrustBadge.
 */
class TrustBadgeCriteria extends Model
{
    use HasUuids;

    protected $fillable = ['trust_badge_id', 'criterion_key', 'description', 'is_mandatory'];

    protected $casts = ['is_mandatory' => 'boolean'];

    public function trustBadge(): BelongsTo { return $this->belongsTo(TrustBadge::class); }
}
