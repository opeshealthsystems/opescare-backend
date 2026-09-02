<?php

namespace App\Enums;

/**
 * ABO/Rh blood groups, as stored in `blood_availability.blood_group`.
 *
 * The CareMap-era table types the column as a plain string; this enum is the
 * canonical vocabulary the patient-facing Blood Finder validates against so a
 * client can never search or request an unknown group. It matches the `in:`
 * list the staff-facing BloodInventoryController already enforces.
 */
enum BloodGroup: string
{
    case APositive  = 'A+';
    case ANegative  = 'A-';
    case BPositive  = 'B+';
    case BNegative  = 'B-';
    case ABPositive = 'AB+';
    case ABNegative = 'AB-';
    case OPositive  = 'O+';
    case ONegative  = 'O-';

    /** The label is the group itself — "A+" reads the same in EN and FR. */
    public function label(): string
    {
        return $this->value;
    }

    /** Whose red cells this group can safely receive (compatibility, not advice). */
    public function canReceiveFrom(): array
    {
        return match ($this) {
            self::ABPositive => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            self::ABNegative => ['A-', 'B-', 'AB-', 'O-'],
            self::APositive  => ['A+', 'A-', 'O+', 'O-'],
            self::ANegative  => ['A-', 'O-'],
            self::BPositive  => ['B+', 'B-', 'O+', 'O-'],
            self::BNegative  => ['B-', 'O-'],
            self::OPositive  => ['O+', 'O-'],
            self::ONegative  => ['O-'],
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $g) => $g->value, self::cases());
    }
}
