<?php

namespace App\Enums;

/**
 * Lifecycle of a patient's medicine reservation (hold) at a pharmacy.
 *
 * DB column: medicine_reservations.status (string, backed value).
 *
 * Lifecycle:
 *   pending → confirmed → ready_for_pickup → collected
 *           ↘ rejected
 *   pending|confirmed|ready_for_pickup → cancelled (patient) | expired (scheduler)
 *
 * A reservation is a *hold*, never a payment. Payment (MTN MoMo / Orange Money)
 * is a deliberate fast-follow — see the Phase 2 note in the mobile design spec.
 */
enum MedicineReservationStatus: string
{
    case Pending        = 'pending';
    case Confirmed      = 'confirmed';
    case ReadyForPickup = 'ready_for_pickup';
    case Collected      = 'collected';
    case Rejected       = 'rejected';
    case Cancelled      = 'cancelled';
    case Expired        = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending        => 'Awaiting Pharmacy',
            self::Confirmed      => 'Confirmed',
            self::ReadyForPickup => 'Ready for Pickup',
            self::Collected      => 'Collected',
            self::Rejected       => 'Declined by Pharmacy',
            self::Cancelled      => 'Cancelled',
            self::Expired        => 'Expired',
        };
    }

    /** Still live — counts against the patient's open-reservation limit. */
    public function isOpen(): bool
    {
        return match ($this) {
            self::Pending, self::Confirmed, self::ReadyForPickup => true,
            self::Collected, self::Rejected, self::Cancelled, self::Expired => false,
        };
    }

    /** Terminal states never transition again (reservations are append-only in effect). */
    public function isTerminal(): bool
    {
        return ! $this->isOpen();
    }

    /** Can the patient still cancel it themselves? */
    public function isCancellableByPatient(): bool
    {
        return match ($this) {
            self::Pending, self::Confirmed, self::ReadyForPickup => true,
            default => false,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $s) => $s->value, self::cases());
    }
}
