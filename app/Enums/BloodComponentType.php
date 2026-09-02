<?php

namespace App\Enums;

/**
 * Blood component types held in `blood_availability.component_type`.
 *
 * The four values below are exactly the ones the care_map migration documents
 * for that column (whole_blood, red_cells, platelets, plasma) — deliberately
 * NOT the staff-side `blood_inventory.component` vocabulary, which is a
 * different table with a different (larger) list. The Blood Finder searches
 * `blood_availability`, so it validates against that table's vocabulary.
 */
enum BloodComponentType: string
{
    case WholeBlood = 'whole_blood';
    case RedCells   = 'red_cells';
    case Platelets  = 'platelets';
    case Plasma     = 'plasma';

    /** English fallback label; the mobile client renders its own i18n string. */
    public function label(): string
    {
        return match ($this) {
            self::WholeBlood => 'Whole Blood',
            self::RedCells   => 'Red Cells',
            self::Platelets  => 'Platelets',
            self::Plasma     => 'Plasma',
        };
    }

    /** Lucide icon key the mobile client maps onto an icon component. */
    public function iconKey(): string
    {
        return match ($this) {
            self::WholeBlood => 'droplet',
            self::RedCells   => 'circle-dot',
            self::Platelets  => 'hexagon',
            self::Plasma     => 'flask-conical',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }
}
