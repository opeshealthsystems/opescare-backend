<?php

namespace App\Enums;

/**
 * Patient Health ID Verification Status
 *
 * Represents the verification lifecycle of a patient's Health ID,
 * tracking how far their identity has been confirmed by the system
 * or a clinical authority.
 *
 * DB column: patients.verification_status (string)
 * Lifecycle:  provisional → pending → verified
 *                                  ↘ suspended / deceased / entered_in_error
 *                                  ↘ merged (duplicate absorbed)
 *                                  ↘ erasure_pending (data subject right exercised)
 *                                  ↘ expired (ID past validity date)
 *
 * MINSANTE: Active Health IDs must be in 'verified' or 'verified_by_facility'.
 * Any other status blocks the patient from appearing in facility searches.
 */
enum VerificationStatus: string
{
    /** Newly created — not yet submitted for verification. */
    case Provisional = 'provisional';

    /** Submitted and awaiting facility or admin verification. */
    case Pending = 'pending';

    /** Not yet been through the verification workflow. */
    case Unverified = 'unverified';

    /** Verified by a registered OpesCare facility (gold standard). */
    case Verified = 'verified';

    /** Verified by a facility (alias used in older records; functionally identical). */
    case VerifiedByFacility = 'verified_by_facility';

    /** Verified by a facility (legacy short form used by older writers/seeders). */
    case FacilityVerified = 'facility_verified';

    /** Suspended — temporarily blocked from use (e.g. fraud investigation). */
    case Suspended = 'suspended';

    /** Patient is deceased — record retained for clinical/legal continuity. */
    case Deceased = 'deceased';

    /** Record created in error — must not be used for clinical lookups. */
    case EnteredInError = 'entered_in_error';

    /** Flagged as a suspected duplicate — pending review/merge decision. */
    case DuplicateSuspected = 'duplicate_suspected';

    /** Record was a duplicate and absorbed into another (canonical) record. */
    case Merged = 'merged';

    /** Patient exercised their erasure right — PII cleared, pending admin deletion. */
    case ErasurePending = 'erasure_pending';

    /** Health ID has passed its validity date (10-year lifetime). */
    case Expired = 'expired';

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Statuses that permit active clinical use (lookups, verification, prescriptions).
     */
    public function isActive(): bool
    {
        return in_array($this, [self::Verified, self::VerifiedByFacility, self::FacilityVerified], true);
    }

    /**
     * Statuses that should block emergency and routine access alike.
     */
    public function isBlocked(): bool
    {
        return in_array($this, [
            self::Suspended,
            self::Deceased,
            self::EnteredInError,
            self::Merged,
            self::ErasurePending,
            self::Expired,
        ], true);
    }

    /**
     * Human-readable label for admin portal display.
     */
    public function label(): string
    {
        return match ($this) {
            self::Provisional      => 'Provisional',
            self::Pending          => 'Pending Verification',
            self::Unverified       => 'Unverified',
            self::DuplicateSuspected => 'Suspected Duplicate',
            self::Verified         => 'Verified',
            self::VerifiedByFacility => 'Verified by Facility',
            self::FacilityVerified   => 'Verified by Facility',
            self::Suspended        => 'Suspended',
            self::Deceased         => 'Deceased',
            self::EnteredInError   => 'Entered in Error',
            self::Merged           => 'Merged (Duplicate)',
            self::ErasurePending   => 'Erasure Pending',
            self::Expired          => 'Expired',
        };
    }

    /**
     * Tailwind CSS colour class for status badge rendering.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Verified, self::VerifiedByFacility,
            self::FacilityVerified                    => 'badge-success',
            self::Pending, self::Provisional,
            self::DuplicateSuspected                  => 'badge-warning',
            self::Unverified                          => 'badge-secondary',
            self::Suspended, self::ErasurePending     => 'badge-danger',
            self::Deceased, self::EnteredInError,
            self::Merged, self::Expired               => 'badge-dark',
        };
    }
}
