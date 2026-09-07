<?php

namespace App\Observers;

use App\Models\MobileInvoice;
use App\Services\Subscriptions\SubscriptionCheckoutService;
use App\Services\Subscriptions\SubscriptionSettlementService;

/**
 * The wire between a settled invoice and the subscription it paid for.
 *
 * ── Why an observer rather than a call inside the payment service ──────────
 * `PaymentChargeService` is the one writer of payment status, it is guarded by
 * a database trigger, and it has no business knowing that subscriptions exist.
 * Teaching it about them would mean every future payable thing — a booking, a
 * dispensing, a lab order — adds another branch to the class that must not
 * grow branches.
 *
 * So the payment rail stays exactly as it is and this listens instead. It
 * reacts only to a status CHANGE (`wasChanged`), and only on an invoice
 * stamped `subject_type = 'subscription'`; every other invoice passes through
 * without a query.
 *
 * ── It runs inside the settlement transaction, on purpose ──────────────────
 * `PaymentChargeService::applyResult()` writes the invoice inside a
 * transaction, so this runs there too. That is the behaviour we want: if
 * granting the period fails, the invoice does not get to stay `paid` on its
 * own — the whole settlement rolls back and the next poll tries again. A
 * subscriber is never charged into a state where the money moved and the
 * subscription silently did not.
 *
 * It is a backstop, not the only path: the subscription status endpoint calls
 * the same settlement service directly, and it is idempotent, so an app that
 * polls gets the answer even if this never fires.
 */
class MobileInvoiceObserver
{
    public function __construct(
        private readonly SubscriptionSettlementService $settlement,
    ) {
    }

    public function updated(MobileInvoice $invoice): void
    {
        if (! $invoice->wasChanged('status')) {
            return;
        }

        if ($invoice->subject_type !== SubscriptionCheckoutService::SUBJECT_TYPE) {
            return;
        }

        $this->settlement->settleFromInvoice($invoice);
    }
}
