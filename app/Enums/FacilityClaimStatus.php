<?php

namespace App\Enums;

/**
 * Lifecycle of a request to manage a directory listing.
 *
 * DB column: facility_claims.claim_status (string, backed value).
 *
 *   pending_verification → pending_review → approved → revoked
 *   (code sent to the       (code entered,     ↘ rejected
 *    listing's phone)        or SMS could not
 *                            be delivered)
 *
 * `submitted` / `under_review` are the pre-2026-10-20 states. They stay
 * readable for old rows but a listing claim in them carries no ownership
 * proof and cannot be approved (FacilityClaimService::approveClaim).
 *
 * There is no path from `submitted` to `approved` that a machine may take.
 * Anyone can assert they run a hospital; deciding whether they do is a
 * judgement about a real institution and a real person, and the platform makes
 * that judgement through a named administrator or not at all. `grantsEditAccess`
 * is therefore true for exactly one case.
 */
enum FacilityClaimStatus: string
{
    case PendingVerification = 'pending_verification';
    case PendingReview       = 'pending_review';
    case Submitted   = 'submitted';
    case UnderReview = 'under_review';
    case Approved    = 'approved';
    case Rejected    = 'rejected';
    case Revoked     = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::PendingVerification => 'Awaiting phone code',
            self::PendingReview       => 'Awaiting review',
            self::Submitted   => 'Awaiting review',
            self::UnderReview => 'Under review',
            self::Approved    => 'Approved',
            self::Rejected    => 'Rejected',
            self::Revoked     => 'Revoked',
        };
    }

    /** Still sitting in the moderation queue. */
    public function isOpen(): bool
    {
        return match ($this) {
            self::PendingVerification, self::PendingReview, self::Submitted, self::UnderReview => true,
            self::Approved, self::Rejected, self::Revoked => false,
        };
    }

    /**
     * The single question every self-service edit asks. Only an administrator
     * moving a claim to Approved can ever make this true.
     */
    public function grantsEditAccess(): bool
    {
        return $this === self::Approved;
    }

    /** Statuses that block a second claim on the same listing by the same user. */
    public static function blocking(): array
    {
        return [
            self::PendingVerification->value, self::PendingReview->value,
            self::Submitted->value, self::UnderReview->value, self::Approved->value,
        ];
    }

    /** The only state a listing claim may be approved from. */
    public function isReviewable(): bool
    {
        return $this === self::PendingReview;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $s) => $s->value, self::cases());
    }
}
