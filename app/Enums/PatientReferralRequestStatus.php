<?php

namespace App\Enums;

/**
 * Lifecycle of a patient's request FOR a referral.
 *
 * DB column: patient_referral_requests.status (string, backed value).
 *
 *   submitted → under_review → accepted   (a clinician issued a referral_case)
 *                            ↘ declined
 *   submitted|under_review   → withdrawn  (the patient took it back)
 *
 * None of these states is a referral. `accepted` is the only one that has a
 * referral behind it, and even then the referral is the `referral_cases` row —
 * this record remains the request that led to it.
 *
 * There is deliberately no `approved` or `sent`: both would read, on a patient's
 * screen, as "you have been referred".
 */
enum PatientReferralRequestStatus: string
{
    /** Filed by the patient. Nobody has looked at it yet. */
    case Submitted = 'submitted';

    /** A clinician has picked it up. Still not a referral. */
    case UnderReview = 'under_review';

    /** A clinician issued a real referral; `referral_case_id` points at it. */
    case Accepted = 'accepted';

    /** A clinician decided a referral is not the right step. */
    case Declined = 'declined';

    /** The patient took their own request back. */
    case Withdrawn = 'withdrawn';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Still awaiting a clinician. Counts against the open-request limit. */
    public function isOpen(): bool
    {
        return match ($this) {
            self::Submitted, self::UnderReview => true,
            self::Accepted, self::Declined, self::Withdrawn => false,
        };
    }

    /** The patient may still take it back. */
    public function isWithdrawable(): bool
    {
        return $this->isOpen();
    }

    /**
     * Does a real referral exist behind this request?
     *
     * The single question the UI must ask before it uses the word "referral".
     */
    public function hasIssuedReferral(): bool
    {
        return $this === self::Accepted;
    }

    public function labelKey(): string
    {
        return 'patient_writes.referral_request.status.' . $this->value;
    }
}
