<?php

namespace App\Support;

/**
 * The four states a platform capability can be in.
 *
 * This replaces a boolean. "On or off" could not express the difference between
 * "we have not launched this yet" and "we have switched it off right now because
 * something is wrong", and those need different words in an audit trail and
 * different permissions to set.
 *
 * Ordered from most to least available. Resolution across the platform →
 * country → organization → facility → user chain always takes the MOST
 * RESTRICTIVE state that applies, so a capability is available only where every
 * level agrees it should be.
 *
 * @see \App\Support\Features
 * @see docs/ARCHITECTURE.md
 */
enum FeatureState: string
{
    /** Shipping. Available to every organization entitled to it. */
    case Live = 'live';

    /** Available to explicitly enrolled organizations only. Not general release. */
    case Pilot = 'pilot';

    /** Out of the current launch scope. Code intact, surface closed. */
    case Frozen = 'frozen';

    /** Was available; switched off deliberately — incident, maintenance, withdrawal. */
    case Disabled = 'disabled';

    /**
     * Does this state grant access at all?
     *
     * Pilot counts as reachable at the PLATFORM level; whether a given
     * organization is actually enrolled is decided one level down. Anything
     * else is closed.
     */
    public function grantsAccess(): bool
    {
        return match ($this) {
            self::Live, self::Pilot => true,
            self::Frozen, self::Disabled => false,
        };
    }

    /** Pilot is reachable, but only for organizations explicitly enrolled. */
    public function requiresEnrolment(): bool
    {
        return $this === self::Pilot;
    }

    /**
     * Lower is more restrictive. Used to pick the winner when several levels
     * of the hierarchy disagree.
     */
    public function restrictiveness(): int
    {
        return match ($this) {
            self::Disabled => 0,
            self::Frozen   => 1,
            self::Pilot    => 2,
            self::Live     => 3,
        };
    }

    /** The most restrictive of two states. */
    public function mostRestrictive(self $other): self
    {
        return $this->restrictiveness() <= $other->restrictiveness() ? $this : $other;
    }

    /**
     * Changing INTO or OUT OF one of these needs an explicit confirmation step
     * in the control centre — they are either core infrastructure or a
     * clinically material network service.
     */
    public function isHighRiskTransition(self $to): bool
    {
        return $this->grantsAccess() !== $to->grantsAccess();
    }

    /**
     * Parse anything into a state, FAILING CLOSED.
     *
     * An unknown string, a null, a boolean left over from the old flag format —
     * all of them read as frozen. The only way to be available is to say so
     * exactly.
     */
    public static function parse(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        // Backward compatibility with the boolean flags this replaced.
        if ($value === true) {
            return self::Live;
        }

        if (! is_string($value)) {
            return self::Frozen;
        }

        return self::tryFrom(strtolower(trim($value))) ?? self::Frozen;
    }

    public function label(): string
    {
        return match ($this) {
            self::Live     => 'Live',
            self::Pilot    => 'Pilot',
            self::Frozen   => 'Frozen',
            self::Disabled => 'Disabled',
        };
    }

    /** Semantic colour token, for the control centre. Never a raw hex here. */
    public function tone(): string
    {
        return match ($this) {
            self::Live     => 'success',
            self::Pilot    => 'info',
            self::Frozen   => 'warning',
            self::Disabled => 'danger',
        };
    }
}
