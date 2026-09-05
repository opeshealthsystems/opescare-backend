<?php

namespace App\Services\Payments\Domain;

/**
 * The lifecycle of an invoice and of the charge that settles it.
 *
 * DB column: mobile_invoices.status / mobile_payment_charges.status.
 *
 *      pending ──► paid ──► refunded
 *         │
 *         ├──► failed
 *         └──► expired
 *
 * The arrows above are the WHOLE map. `paid` never returns to `pending`;
 * `failed` and `expired` are terminal and are never reopened (a patient who
 * wants to try again gets a NEW charge against the same invoice — the failed
 * attempt stays on the record).
 *
 * This enum is the readable copy of that rule. The ENFORCED copy is the
 * `opescare_mobile_payment_guard()` BEFORE UPDATE trigger installed by
 * 2026_09_05_200000_create_mobile_payment_tables.php, which rejects every
 * other pair no matter which process or which language attempts it. If the two
 * ever disagree, the trigger wins and this file is the bug.
 */
enum PaymentStatus: string
{
    case Pending  = 'pending';
    case Paid     = 'paid';
    case Failed   = 'failed';
    case Expired  = 'expired';
    case Refunded = 'refunded';

    /** i18n key under lang/{en,fr}/payments.php. Never a hardcoded label. */
    public function labelKey(): string
    {
        return 'payments.status.'.$this->value;
    }

    public function label(): string
    {
        return __($this->labelKey());
    }

    /** Still in flight — the app should keep polling. */
    public function isOpen(): bool
    {
        return $this === self::Pending;
    }

    /** Settled one way or another; nothing further happens on its own. */
    public function isTerminal(): bool
    {
        return ! $this->isOpen();
    }

    /** Money actually moved (and, for a refund, moved back). */
    public function isSettledFavourably(): bool
    {
        return $this === self::Paid || $this === self::Refunded;
    }

    /**
     * Where this status may legally go next. Mirrors the trigger exactly.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Pending => [self::Paid, self::Failed, self::Expired],
            self::Paid    => [self::Refunded],
            self::Failed, self::Expired, self::Refunded => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->transitions(), true);
    }

    /**
     * The column that timestamps arrival in this status, or null for `pending`
     * (which is the row's own created_at).
     */
    public function timestampColumn(): ?string
    {
        return match ($this) {
            self::Pending  => null,
            self::Paid     => 'paid_at',
            self::Failed   => 'failed_at',
            self::Expired  => 'expired_at',
            self::Refunded => 'refunded_at',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $s) => $s->value, self::cases());
    }
}
