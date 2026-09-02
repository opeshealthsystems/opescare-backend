<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Country — Module 12 (Master Admin Control Center)
 *
 * Platform-level country catalogue used for facility addresses,
 * Health ID formatting, public health reporting, and geo-filtering.
 */
class Country extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'iso2', 'iso3', 'phone_code',
        'currency_code', 'timezone', 'is_active', 'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('display_order')->orderBy('name');
    }
}
