<?php

namespace App\Services\Subscriptions;

use App\Models\Facility;
use App\Models\OrganizationSubscription;
use App\Models\Patient;
use App\Services\Subscriptions\Domain\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Answers "has this subscriber paid, and is that payment still current?" —
 * and answers NOTHING else.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  THIS CLASS DOES NOT LOCK ANYONE OUT, AND THAT IS DELIBERATE
 * ════════════════════════════════════════════════════════════════════════════
 * Nothing in this codebase calls it to refuse a request, hide a record, or
 * gate a screen. Whether a patient who has not paid loses access to their own
 * medical record is a policy decision that has not been made, and it is not a
 * decision a service class gets to make by being convenient to call.
 *
 * What this feature owes that future decision is a TRUE answer, available in
 * one place, with no way to fake it:
 *
 *   - `isCurrentlyPaid()` requires BOTH an access-granting status AND a period
 *     that contains today. A status alone is not enough — a subscription can
 *     be left `active` by a bug; a period cannot be left behind by one,
 *     because it is NULL until an invoice reaches `paid` and it is written
 *     exactly once, by `SubscriptionSettlementService`, from the interval that
 *     was actually paid for.
 *
 *   - A failed or abandoned payment therefore reads `false` here by
 *     construction, not by remembering to check something.
 *
 * ── When someone is ready to enforce ───────────────────────────────────────
 * The two places it would go, and what each already does today:
 *
 *   1. FACILITIES — `App\Http\Middleware\EnforceModuleEntitlement`. It already
 *      resolves the facility, already loads the subscription, and already
 *      fails OPEN when there is none (`if (!$subscription) return $next(...)`).
 *      A facility gate is one condition added there, plus route middleware.
 *      This feature writes `organization_id` on facility subscriptions
 *      precisely so that middleware's existing query finds them.
 *
 *   2. PATIENTS — there is no equivalent, and inventing one was out of scope
 *      and out of bounds. It would be a new middleware on the `auth.mobile`
 *      group, reading this class for the patient the auth middleware stamped.
 *      Whoever writes it has to answer the question above first, and decide
 *      what a patient in the middle of an emergency, or with a pending charge
 *      that has not settled, is entitled to. There is a strong argument that
 *      the answer is "their own record, always".
 */
final class SubscriptionAccessService
{
    /**
     * The subscription that currently speaks for this subscriber, if any.
     *
     * The newest paid-up one wins; if none is paid up, the newest row is
     * returned so a caller can see WHY (pending payment, expired, cancelled)
     * rather than being told there is nothing at all.
     */
    public function currentFor(Model $subscriber): ?OrganizationSubscription
    {
        $rows = $this->forSubscriber($subscriber)->get();

        foreach ($rows as $row) {
            if ($this->isCurrentlyPaid($row)) {
                return $row;
            }
        }

        return $rows->first();
    }

    /**
     * Every subscription this subscriber has ever had, newest first.
     *
     * @return \Illuminate\Database\Eloquent\Builder<OrganizationSubscription>
     */
    public function forSubscriber(Model $subscriber): \Illuminate\Database\Eloquent\Builder
    {
        return OrganizationSubscription::query()
            ->with('plan')
            ->where('subscriber_type', $this->tokenFor($subscriber))
            ->where('subscriber_id', $subscriber->getKey())
            ->latest('created_at');
    }

    /**
     * Paid for, and the period paid for contains today.
     *
     * Both halves are required. `current_period_end` is end-EXCLUSIVE (see
     * `BillingInterval::endFrom()`), so a daily subscription bought today is
     * current for the whole of today and stops being current at midnight.
     */
    public function isCurrentlyPaid(OrganizationSubscription $subscription): bool
    {
        $status = SubscriptionStatus::tryFrom((string) $subscription->status);

        if ($status === null || ! $status->grantsAccess()) {
            return false;
        }

        if ($subscription->current_period_start === null || $subscription->current_period_end === null) {
            return false;
        }

        $today = Carbon::today();

        return $subscription->current_period_start->lessThanOrEqualTo($today)
            && $subscription->current_period_end->greaterThan($today);
    }

    /** Whole days of paid-for access left, or 0. Never negative. */
    public function daysRemaining(OrganizationSubscription $subscription): int
    {
        if (! $this->isCurrentlyPaid($subscription)) {
            return 0;
        }

        return max(0, (int) Carbon::today()->diffInDays($subscription->current_period_end, false));
    }

    /** The morph token for a subscriber model. */
    private function tokenFor(Model $subscriber): string
    {
        return match (true) {
            $subscriber instanceof Patient  => SubscriptionPlanCatalog::SUBSCRIBER_PATIENT,
            $subscriber instanceof Facility => SubscriptionPlanCatalog::SUBSCRIBER_FACILITY,
            default => $subscriber->getMorphClass(),
        };
    }
}
