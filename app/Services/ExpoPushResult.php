<?php

namespace App\Services;

/**
 * The outcome of one push send — deliberately not a boolean.
 *
 * A boolean is what let the previous state of this feature hide: "we called
 * something and it did not throw" is not the same as "a phone was reached".
 * This separates the cases that matter operationally:
 *
 *  - `accepted`        Expo took the message for delivery (a ticket was issued).
 *  - `rejected`        Expo refused it (bad token, rate limit, missing credentials).
 *  - `skippedNoToken`  the stored row does not hold an Expo push token at all —
 *                      the fingerprint rows written by the old mobile client.
 *                      These are NOT failures to retry; they are rows that
 *                      never could be delivered to.
 *  - `retired`         rows deactivated because Expo said `DeviceNotRegistered`.
 *  - `transportError`  the whole request failed (network, non-2xx, bad JSON).
 *  - `suppressedBy`    the patient switched this kind of push off in the app
 *                      (the mobile_app_settings column that silenced it).
 *                      Nothing was attempted, and nothing should be: this is
 *                      the patient's choice, not a failure.
 *
 * `accepted > 0` means Expo queued it, not that a handset displayed it — that
 * is what the push_deliveries ledger and `push:check-receipts` are for, a few
 * minutes later.
 */
final class ExpoPushResult
{
    /**
     * @param  list<string>  $ticketIds     Receipt ids for the accepted messages.
     * @param  list<string>  $errors        Human-readable reasons, for logs only.
     * @param  string|null   $suppressedBy  The preference column that silenced the push, if any.
     */
    public function __construct(
        public readonly int $accepted = 0,
        public readonly int $rejected = 0,
        public readonly int $skippedNoToken = 0,
        public readonly int $retired = 0,
        public readonly array $ticketIds = [],
        public readonly array $errors = [],
        public readonly ?string $transportError = null,
        public readonly ?string $suppressedBy = null,
    ) {}

    /** Nothing was even attempted — no active row held a real push token, or the patient opted out. */
    public function hadNothingToSendTo(): bool
    {
        return $this->accepted === 0
            && $this->rejected === 0
            && $this->transportError === null;
    }

    /** Expo queued at least one message. Not proof of display — see receipts. */
    public function queued(): bool
    {
        return $this->accepted > 0;
    }

    /** The patient's own preference stopped this push before it was built. */
    public function suppressed(): bool
    {
        return $this->suppressedBy !== null;
    }

    public function merge(self $other): self
    {
        return new self(
            accepted: $this->accepted + $other->accepted,
            rejected: $this->rejected + $other->rejected,
            skippedNoToken: $this->skippedNoToken + $other->skippedNoToken,
            retired: $this->retired + $other->retired,
            ticketIds: [...$this->ticketIds, ...$other->ticketIds],
            errors: [...$this->errors, ...$other->errors],
            transportError: $this->transportError ?? $other->transportError,
            suppressedBy: $this->suppressedBy ?? $other->suppressedBy,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'accepted'         => $this->accepted,
            'rejected'         => $this->rejected,
            'skipped_no_token' => $this->skippedNoToken,
            'retired'          => $this->retired,
            'ticket_ids'       => $this->ticketIds,
            'errors'           => $this->errors,
            'transport_error'  => $this->transportError,
            'suppressed_by'    => $this->suppressedBy,
        ];
    }
}
