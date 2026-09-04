<?php

namespace App\Enums;

/**
 * Lifecycle of a clinician's request to join a facility's team.
 *
 * DB column: facility_join_requests.status (string, backed value).
 *
 *   pending → approved
 *           ↘ declined
 *           ↘ withdrawn   (by the requester, never by the facility)
 *
 * Every transition starts at `pending` and ends there. There is deliberately
 * no edge INTO `pending`: a decided request is a historical record, and a
 * "reopen" verb is how a declined request quietly becomes an approved one six
 * months later with nobody's name against the change. Someone who was declined
 * asks again — a new row, a new decision, its own audit trail.
 */
enum FacilityJoinRequestStatus: string
{
    case Pending   = 'pending';
    case Approved  = 'approved';
    case Declined  = 'declined';
    case Withdrawn = 'withdrawn';

    /** Still sitting in the facility's queue. */
    public function isOpen(): bool
    {
        return $this === self::Pending;
    }

    /** Decided, one way or another — nothing may mutate it further. */
    public function isFinal(): bool
    {
        return ! $this->isOpen();
    }

    /** Translation key (lang/en/team.php, lang/fr/team.php) for a human label. */
    public function labelKey(): string
    {
        return 'team.join_status_' . $this->value;
    }

    /** Badge class used by the portal tables. Never purple. */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending   => 'badge-warning',
            self::Approved  => 'badge-success',
            self::Declined  => 'badge-danger',
            self::Withdrawn => 'badge-neutral',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $s) => $s->value, self::cases());
    }
}
