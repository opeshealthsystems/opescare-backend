<?php

namespace App\Services\Payments\Domain;

/**
 * What a provider says about one collection, normalised.
 *
 * Deliberately a different type from `PaymentStatus`. A provider reports on a
 * *transaction*; our `PaymentStatus` is the state of *our record*. Collapsing
 * the two lets a provider write our database, and the single most dangerous
 * value below is exactly why that must not happen:
 *
 *   **Unknown** — the provider could not be reached, answered with something
 *   unrecognised, or returned a status this code has never seen. `Unknown`
 *   must NEVER be mapped to `failed`. A network timeout on a status poll does
 *   not mean the patient's wallet was not debited; treating it as a failure is
 *   how a paid invoice gets charged a second time. `Unknown` leaves the charge
 *   `pending` and lets the poller (or the expiry sweeper) decide.
 */
enum ProviderPaymentStatus: string
{
    /** Accepted, awaiting the payer's PIN on the handset. */
    case Pending = 'pending';

    /** Money moved. The only value that may settle a charge as `paid`. */
    case Succeeded = 'succeeded';

    /** The provider gave a definitive negative answer (rejected, insufficient funds). */
    case Failed = 'failed';

    /** The push prompt lapsed on the handset without a PIN. */
    case Expired = 'expired';

    /** No answer, or an answer this code does not recognise. NOT a failure. */
    case Unknown = 'unknown';

    /**
     * The `PaymentStatus` this justifies moving our record to, or null to leave
     * it exactly where it is.
     */
    public function toRecordStatus(): ?PaymentStatus
    {
        return match ($this) {
            self::Succeeded => PaymentStatus::Paid,
            self::Failed    => PaymentStatus::Failed,
            self::Expired   => PaymentStatus::Expired,
            self::Pending, self::Unknown => null,
        };
    }

    public function isConclusive(): bool
    {
        return $this->toRecordStatus() !== null;
    }
}
