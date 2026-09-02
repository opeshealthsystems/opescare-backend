<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TrustBadge — Trust & Compliance Layer
 *
 * Defines a trust/compliance badge (e.g. "GDPR Compliant", "ISO 27001 Certified",
 * "OpesCare Verified Integration"). Badges are assigned to facilities, orgs, or apps.
 */
class TrustBadge extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'slug', 'description', 'badge_type', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function assignments(): HasMany
    {
        return $this->hasMany(TrustBadgeAssignment::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(TrustBadgeCriteria::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
