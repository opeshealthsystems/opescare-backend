<?php

namespace App\Services\Payments\Dto;

use App\Services\Payments\Domain\ProviderPaymentStatus;

/**
 * What a provider answered — on the initial collection call, on a status poll,
 * or on a verified callback. One shape for all three, so the caller cannot
 * accidentally trust a callback more than a poll.
 *
 * Note what is NOT here: the raw response body. Provider payloads carry the
 * payer's `partyId` — a full MSISDN — and a DTO that carried them would end up
 * in a log the first time someone debugged a failed payment. Only short codes
 * cross this boundary.
 */
final class CollectionResult
{
    public function __construct(
        public readonly ProviderPaymentStatus $status,

        /**
         * The provider's own handle for the transaction:
         *   MTN   — the `X-Reference-Id` UUID (we generate it, they adopt it),
         *           which is also the path segment for the status poll.
         *   Orange— the `pay_token` returned by /webpayment.
         *
         * Null when the call never reached the provider.
         */
        public readonly ?string $providerReference = null,

        /**
         * The provider's status word, verbatim but bounded: 'SUCCESSFUL',
         * 'PENDING', 'FAILED', 'INITIATED', 'EXPIRED'. A code, never a body.
         */
        public readonly ?string $providerStatusCode = null,

        /**
         * A normalised reason, for the i18n key the app renders. One of the
         * FAILURE_* constants below. Never a provider sentence — those are
         * English-only, unstable, and occasionally contain the payer's number.
         */
        public readonly ?string $failureCode = null,

        /**
         * Orange Money's Web Payment flow returns a hosted checkout URL the
         * patient must open to authorise. MTN's Collections flow pushes a PIN
         * prompt straight to the handset and returns none. Null for MTN and
         * for the demo provider, which simulates the handset prompt.
         */
        public readonly ?string $authorizationUrl = null,
    ) {
    }

    // ── Normalised failure codes ────────────────────────────────────────────
    // These, and only these, are what the app translates. Each maps to a key
    // under lang/{en,fr}/payments.php `failure.*`.

    public const FAILURE_INSUFFICIENT_FUNDS = 'insufficient_funds';
    public const FAILURE_REJECTED_BY_PAYER  = 'rejected_by_payer';
    public const FAILURE_PAYER_NOT_FOUND    = 'payer_not_found';
    public const FAILURE_PAYER_LIMIT        = 'payer_limit';
    public const FAILURE_TIMEOUT            = 'timeout';
    public const FAILURE_PROVIDER_ERROR     = 'provider_error';
    public const FAILURE_UNREACHABLE        = 'unreachable';

    /** A result meaning "we could not tell" — always leaves the charge pending. */
    public static function unknown(?string $providerReference = null, ?string $failureCode = null): self
    {
        return new self(
            status: ProviderPaymentStatus::Unknown,
            providerReference: $providerReference,
            failureCode: $failureCode,
        );
    }
}
