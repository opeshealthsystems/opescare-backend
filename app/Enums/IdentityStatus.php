<?php

namespace App\Enums;

/**
 * Patient Identity Status
 *
 * Tracks the completeness and trustworthiness of a patient's identity
 * record — distinct from VerificationStatus which tracks the verification
 * workflow. IdentityStatus captures the semantic state of the record itself.
 *
 * DB column: patients.identity_status (string)
 *
 * Lifecycle:  provisional → unverified → active
 *                                      ↘ verified_by_facility
 *                                      ↘ suspended / deceased / entered_in_error
 *                                      ↘ merged
 *                                      ↘ erasure_pending
 */
enum IdentityStatus: string
{
    /** Initial state when identity created via B2B API before any verification. */
    case Provisional = 'provisional';

    /** Identity exists but has not yet been verified by any authority. */
    case Unverified = 'unverified';

    /** Identity is active and usable for clinical workflows. */
    case Active = 'active';

    /** Identity verified (generic — e.g. by admin or system process). */
    case Verified = 'verified';

    /** Identity verified by a registered facility. */
    case VerifiedByFacility = 'verified_by_facility';

    /** Identity temporarily suspended (e.g. investigation, flag). */
    case Suspended = 'suspended';

    /** Patient is deceased — record must be retained but not used for active care. */
    case Deceased = 'deceased';

    /** Record created in error — clinically invalid, must not be used. */
    case EnteredInError = 'entered_in_error';

    /** Duplicate record that was absorbed into a canonical patient record. */
    case Merged = 'merged';

    /** Patient exercised right to erasure — PII cleared, pending physical deletion. */
    case ErasurePending = 'erasure_pending';

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Returns true if this identity can participate in active clinical care.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::Active, self::Verified, self::VerifiedByFacility], true);
    }

    /**
     * Returns true if this identity should be hidden from routine clinical lookups.
     */
    public function isBlocked(): bool
    {
        return in_array($this, [
            self::Suspended,
            self::Deceased,
            self::EnteredInError,
            self::Merged,
            self::ErasurePending,
        ], true);
    }

    /**
     * Human-readable label for admin portal display.
     */
    public function label(): string
    {
        return match ($this) {
            self::Provisional      => 'Provisional',
            self::Unverified       => 'Unverified',
            self::Active           => 'Active',
            self::Verified         => 'Verified',
            self::VerifiedByFacility => 'Verified by Facility',
            self::Suspended        => 'Suspended',
            self::Deceased         => 'Deceased',
            self::EnteredInError   => 'Entered in Error',
            self::Merged           => 'Merged (Duplicate)',
            self::ErasurePending   => 'Erasure Pending',
        };
    }

    /**
     * Tailwind CSS colour class for badge rendering.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Active, self::Verified,
            self::VerifiedByFacility               => 'badge-success',
            self::Provisional, self::Unverified    => 'badge-warning',
            self::Suspended, self::ErasurePending  => 'badge-danger',
            self::Deceased, self::EnteredInError,
            self::Merged                           => 'badge-dark',
        };
    }
}
