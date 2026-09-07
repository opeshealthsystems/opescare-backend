<?php

namespace App\Services\Subscriptions\Dto;

use App\Models\MobileInvoice;
use App\Models\MobilePaymentCharge;
use App\Models\OrganizationSubscription;
use App\Services\Subscriptions\SubscriptionPresenter;

/**
 * The three rows one "subscribe and pay" produces, and whether they are new.
 *
 * They are returned together because the app needs all three on one screen and
 * must not have to guess how they relate: the SUBSCRIPTION is what was asked
 * for, the INVOICE is what is owed for it, and the CHARGE is the attempt to
 * settle that invoice through a wallet. Only the charge moves on its own — the
 * subscription's period is written when, and only when, the invoice reaches
 * `paid`.
 *
 * `$replayed` is honest reporting, not decoration: it is true when this exact
 * request had already been made under the same `Idempotency-Key`, so the
 * controller answers 200 rather than 201 and the app knows no second prompt
 * was sent to the handset.
 */
final class SubscriptionCheckout
{
    public function __construct(
        public readonly OrganizationSubscription $subscription,
        public readonly MobileInvoice $invoice,
        public readonly MobilePaymentCharge $charge,
        public readonly bool $replayed,
    ) {
    }

    /** @return array<string, mixed> */
    public function toApiArray(): array
    {
        return [
            'subscription' => app(SubscriptionPresenter::class)->toApiArray($this->subscription),
            'invoice'      => $this->invoice->toApiArray(),
            'charge'       => $this->charge->toApiArray(),
        ];
    }
}
