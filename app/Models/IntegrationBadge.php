<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** IntegrationBadge — Marketplace */
class IntegrationBadge extends Model
{
    use HasUuids;
    protected $fillable = ['integration_listing_id', 'trust_badge_id', 'label'];
    public function listing(): BelongsTo { return $this->belongsTo(IntegrationListing::class, 'integration_listing_id'); }
    public function trustBadge(): BelongsTo { return $this->belongsTo(TrustBadge::class); }
}
