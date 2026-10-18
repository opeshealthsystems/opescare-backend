<?php

namespace App\Enums;

/**
 * Lifecycle of an imaging order on the imaging desk.
 *
 * DB column: imaging_orders.status (string, backed value).
 *
 *   pending → scheduled → in_progress → completed
 *   pending|scheduled → cancelled (with a reason)
 *
 * Forward only. A completed order is never reopened; a cancelled one is
 * never resurrected — a new order is raised instead, so history stays whole.
 */
enum ImagingOrderStatus: string
{
    case Pending    = 'pending';
    case Scheduled  = 'scheduled';
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';

    /** Where the desk may move an order from here. */
    public function next(): array
    {
        return match ($this) {
            self::Pending    => [self::Scheduled, self::Cancelled],
            self::Scheduled  => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Completed],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->next(), true);
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Cancelled => true,
            self::Pending, self::Scheduled, self::InProgress => false,
        };
    }

    /** Translation key under lang/{en,fr}/imaging_desk.php. */
    public function labelKey(): string
    {
        return 'imaging_desk.status.' . $this->value;
    }

    public function badgeClass(): string
    {
        return 'badge badge-' . match ($this) {
            self::Completed  => 'success',
            self::InProgress => 'info',
            self::Scheduled  => 'warning',
            self::Cancelled  => 'neutral',
            self::Pending    => 'secondary',
        };
    }
}
