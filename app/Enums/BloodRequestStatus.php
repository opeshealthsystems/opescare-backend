<?php

namespace App\Enums;

/**
 * Lifecycle of a patient's blood-unit request at a blood bank / hospital.
 *
 * DB column: blood_requests.status (string, backed value).
 *
 * Lifecycle:
 *   pending → confirmed → ready → fulfilled
 *           ↘ rejected
 *   pending|confirmed|ready → cancelled (patient) | expired (scheduler)
 *
 * A request is a *reservation of intent*, never a dispense and never a
 * payment — the facility confirms and the patient (or their relative) collects
 * at the counter. Statuses only move forward, mirroring
 * App\Enums\MedicineReservationStatus, so adding facility-side confirmation or
 * payment later never has to rewrite history.
 */
enum BloodRequestStatus: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Ready     = 'ready';
    case Fulfilled = 'fulfilled';
    case Rejected  = 'rejected';
    case Cancelled = 'cancelled';
    case Expired   = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Awaiting Blood Bank',
            self::Confirmed => 'Confirmed',
            self::Ready     => 'Ready for Collection',
            self::Fulfilled => 'Collected',
            self::Rejected  => 'Declined by Blood Bank',
            self::Cancelled => 'Cancelled',
            self::Expired   => 'Expired',
        };
    }

    /** Still live — counts against the patient's open-request limit. */
    public function isOpen(): bool
    {
        return match ($this) {
            self::Pending, self::Confirmed, self::Ready => true,
            self::Fulfilled, self::Rejected, self::Cancelled, self::Expired => false,
        };
    }

    /** Terminal states never transition again. */
    public function isTerminal(): bool
    {
        return ! $this->isOpen();
    }

    /**
     * Where the blood bank may move a request from here. Forward-only.
     *
     * These four were unreachable until the blood-bank queue existed: nothing
     * anywhere could move a request out of `pending` except the patient
     * cancelling or the hourly expiry sweep, so a request was a message into a
     * void. App\Http\Controllers\Api\V1\BloodRequestQueueController is the
     * receiver, and this map is the only rule it enforces.
     *
     * A terminal status transitions to nothing — a request is never reopened
     * and never deleted, exactly as BloodRequestService::expireLapsed treats
     * expiry.
     *
     * @return list<self>
     */
    public function facilityTransitions(): array
    {
        return match ($this) {
            self::Pending   => [self::Confirmed, self::Ready, self::Rejected],
            self::Confirmed => [self::Ready, self::Fulfilled, self::Rejected],
            self::Ready     => [self::Fulfilled, self::Rejected],
            self::Fulfilled, self::Rejected, self::Cancelled, self::Expired => [],
        };
    }

    /** Is this a legal blood-bank move from the current status? */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->facilityTransitions(), true);
    }

    /**
     * Every status a blood bank may ever set, for request validation.
     *
     * @return list<string>
     */
    public static function facilityDecisions(): array
    {
        return [
            self::Confirmed->value,
            self::Ready->value,
            self::Fulfilled->value,
            self::Rejected->value,
        ];
    }

    /** Can the patient still cancel it themselves? */
    public function isCancellableByPatient(): bool
    {
        return match ($this) {
            self::Pending, self::Confirmed, self::Ready => true,
            default => false,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $s) => $s->value, self::cases());
    }
}
