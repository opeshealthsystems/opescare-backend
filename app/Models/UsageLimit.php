<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UsageLimit — Subscription / SaaS Billing (Module 23)
 *
 * Defines the per-feature limits for a subscription plan.
 * limit_value = -1 means unlimited.
 *
 * Note: PlanLimit covers per-plan limits at a granular level for the
 * PlanLimitService. UsageLimit covers the same concept at the
 * SubscriptionPlan → feature_key level with reset periods.
 */
class UsageLimit extends Model
{
    use HasUuids;

    protected $fillable = [
        'subscription_plan_id',
        'feature_key',      // patients|staff|facilities|api_calls|storage_mb
        'limit_value',      // -1 = unlimited
        'reset_period',     // daily|monthly|never
    ];

    protected $casts = [
        'limit_value' => 'integer',
    ];

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function isUnlimited(): bool
    {
        return $this->limit_value < 0;
    }

    public function displayValue(): string
    {
        return $this->isUnlimited() ? 'Unlimited' : number_format($this->limit_value);
    }

    public function scopeForPlan($query, string $planId)
    {
        return $query->where('subscription_plan_id', $planId);
    }

    public static function forPlanAndFeature(string $planId, string $featureKey): ?self
    {
        return static::where('subscription_plan_id', $planId)
            ->where('feature_key', $featureKey)
            ->first();
    }
}
