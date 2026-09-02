<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CountryDistrict — Module 18 (Country Expansion Framework)
 *
 * Sub-regional administrative division within a region.
 * Supports government district ID alignment for public health reporting.
 */
class CountryDistrict extends Model
{
    use HasUuids;

    protected $fillable = [
        'region_id',
        'name',
        'code',
        'government_district_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRegion($query, string $regionId)
    {
        return $query->where('region_id', $regionId);
    }
}
