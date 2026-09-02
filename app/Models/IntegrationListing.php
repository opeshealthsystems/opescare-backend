<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * IntegrationListing — Marketplace / Approved Integrations Directory
 *
 * A developer-submitted listing for the OpesCare integration marketplace.
 * Must be reviewed and approved before being visible.
 */
class IntegrationListing extends Model
{
    use HasUuids;

    protected $fillable = [
        'developer_app_id', 'integration_category_id',
        'name', 'short_description', 'description', 'website',
        'status', 'is_featured',
    ];

    protected $casts = ['is_featured' => 'boolean'];

    public function developerApp(): BelongsTo { return $this->belongsTo(DeveloperApp::class); }
    public function category(): BelongsTo { return $this->belongsTo(IntegrationCategory::class, 'integration_category_id'); }
    public function reviews(): HasMany { return $this->hasMany(IntegrationReview::class); }
    public function badges(): HasMany { return $this->hasMany(IntegrationBadge::class); }

    public function isApproved(): bool { return $this->status === 'approved'; }

    public function approve(): void { $this->update(['status' => 'approved']); }
    public function reject(): void { $this->update(['status' => 'rejected']); }

    public function averageRating(): float
    {
        $published = $this->reviews()->where('is_published', true)->get();
        return $published->isEmpty() ? 0.0 : round($published->avg('rating'), 1);
    }

    public function scopeApproved($query) { return $query->where('status', 'approved'); }
}
