<?php

namespace App\Services\Subscriptions;

use App\Models\OrganizationSubscription;
use App\Services\Subscriptions\Domain\SubscriptionStatus;

/**
 * The wire shape of a subscription.
 *
 * Separate from the model on purpose: `organization_subscriptions` predates
 * this feature, carries legacy columns (`organization_id`, `discount_percent`,
 * the win-back timestamps) that no client should see, and is read by older
 * screens that must not change shape because an API wanted a field.
 *
 * ── What this deliberately reports, and does not ───────────────────────────
 * `is_paid_up` is computed, not stored, and it is the ONLY field that answers
 * "has this been paid for and is the period current?". It is `false` for every
 * subscription that has not been paid, including one whose charge failed —
 * there is no combination of stored values that can make an unpaid
 * subscription look paid, because the period is NULL until money arrives.
 *
 * It is a statement of fact, not an instruction: nothing in this codebase
 * denies anybody anything on the strength of it. See
 * `SubscriptionAccessService` for where a future policy would read it.
 */
final class SubscriptionPresenter
{
    public function __construct(
        private readonly SubscriptionAccessService $access,
    ) {
    }

    /** @return array<string, mixed> */
    public function toApiArray(OrganizationSubscription $subscription): array
    {
        $status = SubscriptionStatus::tryFrom((string) $subscription->status);

        return [
            'id'              => $subscription->id,
            'subscriber_type' => $subscription->subscriber_type,
            'subscriber_id'   => $subscription->subscriber_id,
            'plan'            => [
                'id'   => $subscription->plan_id,
                'slug' => $subscription->plan?->slug,
                'name' => $subscription->plan?->name,
            ],
            'interval'        => $subscription->interval,
            'interval_label'  => __('subscriptions.interval.'.$subscription->interval),
            'status'          => $subscription->status,
            // Never the raw enum value: a French subscriber reads this.
            'status_label'    => $status?->label() ?? (string) $subscription->status,
            // Null until an invoice for this subscription is actually paid.
            'current_period_start' => $subscription->current_period_start?->toDateString(),
            'current_period_end'   => $subscription->current_period_end?->toDateString(),
            'is_paid_up'      => $this->access->isCurrentlyPaid($subscription),
            'days_remaining'  => $this->access->daysRemaining($subscription),
            // How the money arrived, and what to quote to support about it.
            'payment_reference' => $subscription->payment_reference,
            'payment_method'    => $subscription->payment_method,
            // Mobile money holds no mandate, so this is always false and the
            // app must say so rather than implying a silent renewal.
            'auto_renew'      => (bool) $subscription->auto_renew,
            'created_at'      => $subscription->created_at?->toIso8601String(),
        ];
    }
}
