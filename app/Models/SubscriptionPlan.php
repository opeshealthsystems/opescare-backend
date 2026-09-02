<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'slug', 'audience', 'billing_cycle', 'price_kobo', 'annual_price_kobo', 'currency',
        'description', 'features', 'max_facilities', 'max_staff',
        'max_patients_per_month', 'is_active', 'is_public', 'trial_days',
        'sort_order', 'created_by',
    ];

    protected $casts = [
        'features'                 => 'array',
        'is_active'                => 'boolean',
        'is_public'                => 'boolean',
        'price_kobo'               => 'integer',
        'annual_price_kobo'        => 'integer',
        'max_facilities'           => 'integer',
        'max_staff'                => 'integer',
        'max_patients_per_month'   => 'integer',
        'trial_days'               => 'integer',
        'sort_order'               => 'integer',
    ];

    public function planFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class, 'plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(OrganizationSubscription::class, 'plan_id');
    }

    /** Price in major currency unit (XAF / FCFA) */
    public function priceFormatted(): string
    {
        $amount = $this->price_kobo / 100;
        return $this->currency . ' ' . number_format($amount, 0);
    }

    /** Annual price in major currency unit, or null when no annual option. */
    public function annualPriceFormatted(): ?string
    {
        if ($this->annual_price_kobo === null) {
            return null;
        }
        return $this->currency . ' ' . number_format($this->annual_price_kobo / 100, 0);
    }

    /** Resolve the price (minor units) for a given billing interval. */
    public function priceForInterval(string $interval): int
    {
        return $interval === 'annual'
            ? (int) ($this->annual_price_kobo ?? $this->price_kobo * 12)
            : (int) $this->price_kobo;
    }

    public function isFree(): bool
    {
        return (int) $this->price_kobo === 0 && (int) ($this->annual_price_kobo ?? 0) === 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /** e.g. SubscriptionPlan::forAudience('patient')->active()->public()->get() */
    public function scopeForAudience($query, string $audience)
    {
        return $query->where('audience', $audience);
    }

    public function hasFeature(string $featureKey): bool
    {
        return $this->planFeatures()->where('feature_key', $featureKey)->exists();
    }
}
