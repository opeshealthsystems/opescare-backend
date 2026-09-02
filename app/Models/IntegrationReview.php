<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IntegrationReview — Marketplace
 * A facility or organization review of a marketplace integration listing.
 */
class IntegrationReview extends Model
{
    use HasUuids;

    protected $fillable = [
        'integration_listing_id', 'reviewer_id', 'reviewer_type',
        'rating', 'review_text', 'is_published',
    ];

    protected $casts = ['rating' => 'integer', 'is_published' => 'boolean'];

    public function listing(): BelongsTo { return $this->belongsTo(IntegrationListing::class, 'integration_listing_id'); }

    public function publish(): void { $this->update(['is_published' => true]); }

    public function scopePublished($query) { return $query->where('is_published', true); }
}
