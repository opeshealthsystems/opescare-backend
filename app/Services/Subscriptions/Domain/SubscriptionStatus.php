<?php

namespace App\Services\Subscriptions\Domain;

/**
 * What a subscription row is currently claiming about its subscriber.
 *
 * `organization_subscriptions.status` is a plain string column written before
 * this enum existed, and several older readers compare it to string literals.
 * This enum is therefore the *typed* copy of that vocabulary, not a cast on
 * the model: every write goes through `->value`, so nothing existing changes
 * shape and nothing new hardcodes a bare string.
 *
 * ── `pending_payment` is the one this feature adds, and it matters ─────────
 * It is the state of a subscription that has been asked for and NOT paid for.
 * It is deliberately not in `OrganizationSubscription::isActive()`'s list, and
 * a row in this state carries a NULL `current_period_start/end` — so there is
 * nothing for a future entitlement check to mistake for access. A subscriber
 * whose payment failed sits here, truthfully, until they pay or the invoice
 * lapses.
 *
 * ── What is NOT decided here ───────────────────────────────────────────────
 * Nothing in this file, or anywhere in this feature, denies anybody anything.
 * Whether an unpaid patient keeps access to their own medical record is a
 * policy question nobody has answered; this enum only records the truth so
 * that whoever answers it has something correct to enforce against.
 */
enum SubscriptionStatus: string
{
    /** Asked for, invoiced, not paid. Grants nothing. */
    case PendingPayment = 'pending_payment';

    /** Inside a free trial the plan granted. */
    case Trialing = 'trialing';

    /** Paid, and inside the period that was paid for. */
    case Active = 'active';

    /** Was active; a renewal was not settled. Legacy value, kept for readers. */
    case PastDue = 'past_due';

    /** Suspended by an operator. Legacy value, kept for readers. */
    case Paused = 'paused';

    /** Ended by the subscriber or by a refund. */
    case Cancelled = 'cancelled';

    /** The period ran out, or the invoice lapsed before it was ever paid. */
    case Expired = 'expired';

    /** i18n key under lang/{en,fr}/subscriptions.php. */
    public function labelKey(): string
    {
        return 'subscriptions.status.'.$this->value;
    }

    public function label(): string
    {
        return __($this->labelKey());
    }

    /**
     * Does this status, ON ITS OWN, mean the subscriber has paid-for access?
     *
     * "On its own" is the caveat that keeps this honest: `active` is necessary
     * but not sufficient — the period still has to contain today. Ask
     * `SubscriptionAccessService::isCurrentlyPaid()` instead of this, unless
     * all you want is the badge colour.
     */
    public function grantsAccess(): bool
    {
        return $this === self::Active || $this === self::Trialing;
    }

    /** Nothing further happens to this row on its own. */
    public function isTerminal(): bool
    {
        return $this === self::Cancelled || $this === self::Expired;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $s) => $s->value, self::cases());
    }
}
