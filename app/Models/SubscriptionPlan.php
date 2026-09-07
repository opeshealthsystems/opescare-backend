<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\Payments\Domain\Money;

class SubscriptionPlan extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'slug', 'audience', 'billing_cycle',
        'price_minor', 'daily_price_minor', 'monthly_price_minor', 'annual_price_minor', 'currency',
        'description', 'features', 'max_facilities', 'max_staff',
        'max_patients_per_month', 'is_active', 'is_public', 'trial_days',
        'sort_order', 'created_by',
    ];

    protected $casts = [
        'features'                 => 'array',
        'is_active'                => 'boolean',
        'is_public'                => 'boolean',
        'price_minor'              => 'integer',
        'daily_price_minor'        => 'integer',
        'monthly_price_minor'      => 'integer',
        'annual_price_minor'       => 'integer',
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

    /** The intervals a subscriber may actually choose, in ascending length. */
    public const INTERVALS = ['daily', 'monthly', 'annual'];

    /**
     * The price for one interval, or null when this plan is not sold that way.
     *
     * A null is the whole point: a facility is billed yearly and must not be
     * offerable a daily rate just because a column exists.
     */
    public function amountFor(string $interval): ?Money
    {
        $minor = match ($interval) {
            'daily'   => $this->daily_price_minor,
            'monthly' => $this->monthly_price_minor,
            'annual'  => $this->annual_price_minor,
            default   => null,
        };

        // A zero price is a real answer — a free plan — and must survive the
        // null check below.
        return $minor === null ? null : Money::ofMinor((int) $minor, $this->currency ?: 'XAF');
    }

    /** @return list<string> the intervals this plan is sold in */
    public function intervalsOffered(): array
    {
        return array_values(array_filter(
            self::INTERVALS,
            fn (string $interval) => $this->amountFor($interval) !== null
        ));
    }

    public function offersInterval(string $interval): bool
    {
        return $this->amountFor($interval) !== null;
    }

    /**
     * What the subscriber sees, rendered by the same class that renders every
     * other amount in this app.
     *
     * NOTE there is no division by 100 anywhere here. XAF has an ISO 4217
     * exponent of 0 — see Money — so the stored integer IS the number of
     * francs. The old `price_kobo` column divided by 100 and called the result
     * FCFA, which would have shown a 25 000 FCFA plan as 250.
     */
    public function priceFormatted(?string $interval = null): string
    {
        $amount = $this->amountFor($interval ?? $this->defaultInterval());

        return $amount?->format() ?? '—';
    }

    public function annualPriceFormatted(): ?string
    {
        return $this->amountFor('annual')?->format();
    }

    /** The interval the plan leads with. */
    public function defaultInterval(): string
    {
        if (in_array($this->billing_cycle, self::INTERVALS, true) && $this->offersInterval($this->billing_cycle)) {
            return $this->billing_cycle;
        }

        return $this->intervalsOffered()[0] ?? 'annual';
    }

    /** Minor units for an interval, for the payment rail. */
    public function priceForInterval(string $interval): int
    {
        return $this->amountFor($interval)?->minor ?? 0;
    }

    public function isFree(): bool
    {
        foreach ($this->intervalsOffered() as $interval) {
            if ($this->priceForInterval($interval) > 0) {
                return false;
            }
        }

        return true;
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
