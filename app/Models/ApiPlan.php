<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ApiPlan — a metered/billed tier for integration clients (Sandbox / Growth /
 * Scale). Defines the monthly request quota, per-minute rate limit, XAF price,
 * and the feature set surfaced on the public pricing page. Seeded reference data.
 */
class ApiPlan extends Model
{
    protected $fillable = [
        'key', 'name', 'rate_limit_per_min', 'monthly_request_quota',
        'price_xaf', 'overage_price_xaf', 'support_level', 'features', 'is_public', 'sort',
    ];

    protected $casts = [
        'features'              => 'array',
        'is_public'             => 'boolean',
        'rate_limit_per_min'    => 'integer',
        'monthly_request_quota' => 'integer',
        'price_xaf'             => 'integer',
        'overage_price_xaf'    => 'float',
        'sort'                  => 'integer',
    ];

    public static function forKey(?string $key): ?self
    {
        return $key ? static::where('key', $key)->first() : null;
    }

    /** Public plans for the pricing page, in display order. */
    public static function public(): \Illuminate\Support\Collection
    {
        return static::where('is_public', true)->orderBy('sort')->get();
    }

    public function isUnlimited(): bool
    {
        return $this->monthly_request_quota === null;
    }
}
