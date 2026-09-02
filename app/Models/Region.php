<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Region — Module 12 (Master Admin Control Center)
 *
 * Province, state, or region within a country.
 * Used for facility location, public health reporting, and care map filtering.
 */
class Region extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id', 'name', 'code', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('name');
    }
}
