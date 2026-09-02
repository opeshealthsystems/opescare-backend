<?php

namespace App\Modules\Subscription\Services;

use App\Models\PlanLimit;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionUsageMetric;

/**
 * PlanLimitService — Module 23 (Subscription Billing & SaaS Model)
 *
 * Checks and enforces plan-level usage limits.
 * Called by API middleware and billing service to block overages.
 */
class PlanLimitService
{
    /**
     * Check if an organization's current usage for a limit_key is within the plan limit.
     * Returns true if usage is within bounds (or limit is unlimited).
     */
    public function isWithinLimit(
        OrganizationSubscription $subscription,
        string $limitKey,
        int $currentUsage
    ): bool {
        $limit = PlanLimit::where('plan_id', $subscription->plan_id)
            ->where('limit_key', $limitKey)
            ->first();

        if (! $limit) {
            // No limit defined — unrestricted
            return true;
        }

        if ($limit->isUnlimited()) {
            return true;
        }

        return $currentUsage < $limit->limit_value;
    }

    /**
     * Get the limit value for a given plan and limit key.
     * Returns -1 for unlimited, null if no limit is configured.
     */
    public function getLimit(string $planId, string $limitKey): ?int
    {
        $limit = PlanLimit::where('plan_id', $planId)
            ->where('limit_key', $limitKey)
            ->first();

        return $limit?->limit_value;
    }

    /**
     * Get all limits for a subscription plan as a key-value map.
     */
    public function getLimitsForPlan(string $planId): array
    {
        return PlanLimit::where('plan_id', $planId)
            ->get()
            ->pluck('limit_value', 'limit_key')
            ->toArray();
    }

    /**
     * Calculate usage percentage for a limit key.
     * Returns 0-100 (or null if unlimited).
     */
    public function usagePercent(
        OrganizationSubscription $subscription,
        string $limitKey,
        int $currentUsage
    ): ?float {
        $limitValue = $this->getLimit($subscription->plan_id, $limitKey);

        if ($limitValue === null || $limitValue < 0) {
            return null; // unlimited
        }

        if ($limitValue === 0) {
            return 100.0;
        }

        return min(100.0, round(($currentUsage / $limitValue) * 100, 1));
    }
}
