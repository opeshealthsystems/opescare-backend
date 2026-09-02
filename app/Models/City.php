<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * City — Geographic Hierarchy (Country Expansion Framework)
 *
 * City-level administrative unit, child of Region.
 * Optionally linked to a CountryDistrict for detailed geographic hierarchy.
 * Used for facility location, patient address, and reporting.
 */
class City extends Model
{
    use HasUuids;

    protected $fillable = [
        'region_id',
        'district_id',  // FK to country_districts (nullable)
        'name',
        'code',
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

    public function district(): BelongsTo
    {
        return $this->belongsTo(CountryDistrict::class, 'district_id');
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
