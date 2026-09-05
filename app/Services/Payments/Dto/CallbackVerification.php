<?php

namespace App\Services\Payments\Dto;

use App\Services\Payments\Domain\ProviderPaymentStatus;

/**
 * The outcome of checking whether an inbound callback is genuine.
 *
 * `verified` is a hard gate: an unverified callback is dropped, logged as a
 * rejection, and answered 202 anyway (see the callback controller for why a
 * 4xx to an unauthenticated caller is itself an oracle).
 *
 * `claimedStatus` is named "claimed" on purpose. Even a *verified* callback is
 * only a hint that something changed. Neither MTN nor Orange guarantees
 * ordering or exactly-once delivery, and MTN's callback carries no signature at
 * all — so the pipeline always re-polls the provider before writing a terminal
 * status. Nothing in this system settles an invoice on the strength of an
 * inbound HTTP body.
 */
final class CallbackVerification
{
    public function __construct(
        public readonly bool $verified,

        /** Which charge the callback is about — OUR id, from `externalId` / `order_id`. */
        public readonly ?string $externalReference = null,

        /** The provider's transaction handle, for the confirming poll. */
        public readonly ?string $providerReference = null,

        /** What the callback SAYS happened. A hint. Never written directly. */
        public readonly ProviderPaymentStatus $claimedStatus = ProviderPaymentStatus::Unknown,

        /** Why verification failed, for the rejection log. Never echoed to the caller. */
        public readonly ?string $rejectionReason = null,
    ) {
    }

    public static function reject(string $reason): self
    {
        return new self(verified: false, rejectionReason: $reason);
    }
}
