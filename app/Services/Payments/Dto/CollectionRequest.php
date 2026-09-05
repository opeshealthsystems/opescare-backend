<?php

namespace App\Services\Payments\Dto;

use App\Services\Payments\Domain\Money;
use App\Services\Payments\Domain\Msisdn;
use App\Services\Payments\Domain\PaymentMethod;

/**
 * "Take this much money from this wallet."
 *
 * One shape for both rails, because both take the same five facts. Where the
 * two APIs differ is in what they DO with them — see the field notes below and
 * the mapping table in `MobileMoneyProvider`.
 */
final class CollectionRequest
{
    public function __construct(
        /**
         * OUR id for this attempt (the `mobile_payment_charges.id` UUID).
         *
         * MTN calls this `externalId` and echoes it back on the callback.
         * Orange calls it `order_id`. Either way it is how a webhook we did not
         * initiate is matched to a row — so it must be OUR id and never the
         * provider's.
         */
        public readonly string $externalReference,

        /** Integer minor units, already validated. Never a float. */
        public readonly Money $amount,

        /**
         * The payer's wallet. Held as an Msisdn so nothing downstream can
         * casually log it — `forProvider()` is the only accessor that yields
         * the real digits.
         */
        public readonly Msisdn $payer,

        /** Which rail the patient chose. */
        public readonly PaymentMethod $method,

        /**
         * Shown to the payer on the handset prompt. MTN truncates
         * `payerMessage` at 160 characters; Orange has no equivalent field on
         * the web-payment call, so it is used as the order label instead.
         * It must never contain patient-identifying detail — it appears on a
         * screen the patient may not be holding.
         */
        public readonly string $description,

        /**
         * Where the provider should call back. MTN reads it from the
         * `X-Callback-Url` request header (per-request, which is why it is on
         * this DTO rather than in config); Orange reads `notif_url` from the
         * request body.
         */
        public readonly ?string $callbackUrl = null,
    ) {
    }
}
