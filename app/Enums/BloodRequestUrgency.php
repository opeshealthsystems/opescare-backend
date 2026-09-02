<?php

namespace App\Enums;

/**
 * How soon the patient needs the units.
 *
 * This is patient-declared context for the blood bank, never a clinical triage
 * decision made by the platform — the facility still decides what it releases
 * and in what order.
 */
enum BloodRequestUrgency: string
{
    case Routine   = 'routine';
    case Urgent    = 'urgent';
    case Emergency = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::Routine   => 'Routine',
            self::Urgent    => 'Urgent',
            self::Emergency => 'Emergency',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $u) => $u->value, self::cases());
    }
}
