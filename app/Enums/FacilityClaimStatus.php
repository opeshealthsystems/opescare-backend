<?php

namespace App\Enums;

/**
 * Lifecycle of a request to manage a directory listing.
 *
 * DB column: facility_claims.claim_status (string, backed value).
 *
 *   submitted → under_review → approved → revoked
 *                            ↘ rejected
 *
 * There is no path from `submitted` to `approved` that a machine may take.
 * Anyone can assert they run a hospital; deciding whether they do is a
 * judgement about a real institution and a real person, and the platform makes
 * that judgement through a named administrator or not at all. `grantsEditAccess`
 * is therefore true for exactly one case.
 */
enum FacilityClaimStatus: string
{
    case Submitted   = 'submitted';
    case UnderReview = 'under_review';
    case Approved    = 'approved';
    case Rejected    = 'rejected';
    case Revoked     = 'revoked';

    public function label(): string
    {
        return match ($this) {
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
            self::Submitted, self::UnderReview => true,
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
        return [self::Submitted->value, self::UnderReview->value, self::Approved->value];
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $s) => $s->value, self::cases());
    }
}
