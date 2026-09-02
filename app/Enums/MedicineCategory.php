<?php

namespace App\Enums;

/**
 * Consumer-facing medicine category used by the patient Medicine Finder.
 *
 * DB column: medicines.category (string, holds the backed value).
 *
 * These are deliberately *shopper* categories (what a patient searches for),
 * not ATC therapeutic classes — the ATC code is stored separately on the
 * medicine record for clinical/interoperability use.
 */
enum MedicineCategory: string
{
    case PainRelief  = 'pain_relief';
    case Antibiotics = 'antibiotics';
    case Diabetes    = 'diabetes';
    case Cardio      = 'cardio';
    case Vitamins    = 'vitamins';
    case Respiratory = 'respiratory';
    case SkinCare    = 'skin_care';
    case Digestive   = 'digestive';
    case Antimalarial = 'antimalarial';
    case MaternalChild = 'maternal_child';
    case Other       = 'other';

    /** Human-readable English label (the app translates via its own i18n keys). */
    public function label(): string
    {
        return match ($this) {
            self::PainRelief    => 'Pain Relief',
            self::Antibiotics   => 'Antibiotics',
            self::Diabetes      => 'Diabetes',
            self::Cardio        => 'Cardio',
            self::Vitamins      => 'Vitamins',
            self::Respiratory   => 'Respiratory',
            self::SkinCare      => 'Skin Care',
            self::Digestive     => 'Digestive',
            self::Antimalarial  => 'Antimalarial',
            self::MaternalChild => 'Maternal & Child',
            self::Other         => 'Other',
        };
    }

    /**
     * Stable icon key the mobile client maps to a Lucide icon. Never an emoji.
     */
    public function iconKey(): string
    {
        return match ($this) {
            self::PainRelief    => 'pill',
            self::Antibiotics   => 'shield-plus',
            self::Diabetes      => 'droplet',
            self::Cardio        => 'heart-pulse',
            self::Vitamins      => 'flask-conical',
            self::Respiratory   => 'wind',
            self::SkinCare      => 'sparkles',
            self::Digestive     => 'circle-dot',
            self::Antimalarial  => 'bug',
            self::MaternalChild => 'baby',
            self::Other         => 'package',
        };
    }

    /** @return list<string> every backed value — for validation rules. */
    public static function values(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }
}
