<?php

namespace App\Services\Subscriptions;

use App\Models\MobileInvoice;
use App\Models\MobilePaymentCharge;
use App\Models\OrganizationSubscription;
use App\Services\Payments\Domain\PaymentStatus;
use App\Services\Subscriptions\Domain\BillingInterval;
use App\Services\Subscriptions\Domain\SubscriptionStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turns a settled invoice into a subscription period — or into nothing.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  THE ONE RULE
 * ════════════════════════════════════════════════════════════════════════════
 * A period is granted if and only if the invoice reads `paid`. Not "the charge
 * was accepted", not "the provider said it probably worked", not "the app
 * showed a spinner for long enough" — `mobile_invoices.status = 'paid'`, a
 * value that only `PaymentChargeService` can write, only forward, under a row
 * lock, with a Postgres trigger refusing every other transition.
 *
 * Everything else grants nothing, and grants it explicitly:
 *
 *   failed   → the subscription stays `pending_payment`. The invoice is still
 *              open by design (a declined wallet is not an unpayable bill), so
 *              the subscriber can try another wallet and the same subscription
 *              is what gets paid for.
 *   expired  → the invoice lapsed unpaid. The subscription becomes `expired`
 *              WITHOUT ever having had a period. Nothing to revoke, because
 *              nothing was granted.
 *   refunded → the money went back, so the access goes back with it: the
 *              subscription is `cancelled` and its period is cleared.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  IDEMPOTENT, BECAUSE IT IS CALLED FROM THREE DIRECTIONS
 * ════════════════════════════════════════════════════════════════════════════
 * A settlement can arrive by poll, by provider callback, or by the sweeper,
 * and the app may be asking for the subscription's status at the same moment.
 * So every branch below is guarded by the state it is moving FROM, the write
 * happens under `SELECT … FOR UPDATE`, and re-running it changes nothing. Two
 * callers settling the same invoice at the same instant produce one period,
 * not two, and never two different ones.
 *
 * The period is computed from `$subscription->interval` — the interval that
 * was actually invoiced and paid for — never from the plan's current default,
 * which the owner may have changed since.
 */
final class SubscriptionSettlementService
{
    /**
     * Bring the subscription behind this invoice up to date. Safe to call at
     * any time, from anywhere, as often as you like.
     *
     * @return OrganizationSubscription|null null when the invoice is not a
     *         subscription invoice, or its subscription has gone.
     */
    public function settleFromInvoice(MobileInvoice $invoice): ?OrganizationSubscription
    {
        if ($invoice->subject_type !== SubscriptionCheckoutService::SUBJECT_TYPE || $invoice->subject_id === null) {
            return null;
        }

        $subscription = OrganizationSubscription::find($invoice->subject_id);

        if ($subscription === null) {
            // An invoice pointing at a subscription that no longer exists is a
            // data problem worth seeing, not a crash worth causing inside a
            // payment transaction.
            Log::warning('Subscription invoice points at a subscription that no longer exists.', [
                'invoice_reference' => $invoice->reference,
                'subscription_id'   => $invoice->subject_id,
            ]);

            return null;
        }

        return match ($invoice->status) {
            PaymentStatus::Paid     => $this->grantPeriod($subscription, $invoice),
            PaymentStatus::Expired  => $this->lapse($subscription),
            PaymentStatus::Refunded => $this->reverse($subscription),
            // `pending` and `failed` both mean the invoice is still open: the
            // subscriber may yet pay it, so nothing changes.
            default => $subscription,
        };
    }

    /**
     * Write the period that was paid for. Once, and only once.
     */
    private function grantPeriod(OrganizationSubscription $subscription, MobileInvoice $invoice): OrganizationSubscription
    {
        return DB::transaction(function () use ($subscription, $invoice) {
            /** @var OrganizationSubscription $locked */
            $locked = OrganizationSubscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();

            // Re-read AFTER the lock. A concurrent poll may have granted it
            // while this one waited, and that grant stands.
            if ($locked->current_period_start !== null && $locked->current_period_end !== null) {
                return $locked;
            }

            $interval = BillingInterval::tryFrom((string) $locked->interval);

            if ($interval === null) {
                // Refusing to guess is the right move: a period computed from
                // an interval nobody recognises is a period nobody can defend.
                // The money is not lost — the invoice stays `paid` and this
                // will settle correctly once the row is fixed.
                Log::error('Subscription carries an interval this application cannot price a period for.', [
                    'subscription_id' => $locked->id,
                    'interval'        => $locked->interval,
                ]);

                return $locked;
            }

            // Dated from when the money actually arrived, not from now: a
            // callback that reaches us at 00:04 must not shorten a day the
            // subscriber paid for at 23:58.
            $start = Carbon::instance($invoice->paid_at ?? Carbon::now())->startOfDay();

            $paidCharge = $invoice->paidCharge();

            $locked->forceFill([
                'status'               => SubscriptionStatus::Active->value,
                'current_period_start' => $start,
                'current_period_end'   => $interval->endFrom($start),

                // What to quote to support, and what actually paid it. The
                // invoice reference is the human-quotable half (`OC-SUB-…`);
                // the charge is reachable from it, and carries the masked
                // wallet number. The full number is not stored anywhere.
                'payment_reference'    => $invoice->reference,
                'payment_method'       => $paidCharge?->method?->value,

                // Restated at settlement, not merely inherited: mobile money
                // holds no mandate, so nothing here will renew on its own and
                // the record must not imply that it will.
                'auto_renew'           => false,

                'updated_by'           => 'subscription_settlement',
            ])->save();

            return $locked;
        });
    }

    /**
     * The invoice lapsed unpaid. Close the subscription that was waiting on it.
     */
    private function lapse(OrganizationSubscription $subscription): OrganizationSubscription
    {
        return DB::transaction(function () use ($subscription) {
            /** @var OrganizationSubscription $locked */
            $locked = OrganizationSubscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();

            // Only a subscription that never got a period is closed here. One
            // that was paid for earlier and has since had a second invoice
            // lapse keeps the period it paid for — an unpaid RENEWAL must not
            // retroactively cancel the month somebody already bought.
            if ($locked->status !== SubscriptionStatus::PendingPayment->value) {
                return $locked;
            }

            $locked->forceFill([
                'status'     => SubscriptionStatus::Expired->value,
                'expires_at' => Carbon::today(),
                'updated_by' => 'subscription_settlement',
            ])->save();

            return $locked;
        });
    }

    /**
     * The money went back. So does the access.
     */
    private function reverse(OrganizationSubscription $subscription): OrganizationSubscription
    {
        return DB::transaction(function () use ($subscription) {
            /** @var OrganizationSubscription $locked */
            $locked = OrganizationSubscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === SubscriptionStatus::Cancelled->value) {
                return $locked;
            }

            $locked->forceFill([
                'status'               => SubscriptionStatus::Cancelled->value,
                'cancelled_at'         => Carbon::today(),
                // Cleared, not left standing: a refunded period is a period
                // that was not, in the end, paid for.
                'current_period_start' => null,
                'current_period_end'   => null,
                'updated_by'           => 'subscription_settlement',
            ])->save();

            return $locked;
        });
    }

    /**
     * Convenience for a caller holding the charge rather than the invoice —
     * the poll path. Settles from the charge's own invoice, or does nothing.
     */
    public function settleFromCharge(MobilePaymentCharge $charge): ?OrganizationSubscription
    {
        $invoice = $charge->invoice?->fresh();

        return $invoice === null ? null : $this->settleFromInvoice($invoice);
    }
}
