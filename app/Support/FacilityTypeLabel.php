<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

/**
 * The one place a facility type code (`facilities.type`,
 * `care_facilities.facility_type`) becomes words a reader sees.
 *
 * Every value the data can hold is listed in KNOWN and has an EN and FR label
 * under finder_directory.types (held by FacilityTypeLabelTest). A value the
 * list does not know — a new importer, a hand-edited row — falls back to the
 * translated generic "Facility", never to the raw code.
 */
final class FacilityTypeLabel
{
    /** Directory + tenant vocabularies, from the seeders, importers and live data. */
    public const KNOWN = [
        // public directory (care_facilities) — OsmFacilityNormalizer families
        'hospital', 'clinic', 'pharmacy', 'health_center', 'laboratory', 'dental',
        'imaging_center', 'eye_clinic', 'specialist', 'diagnostic_center', 'maternity',
        'dispensary', 'blood_bank', 'nursing_home',
        // tenant-only (facilities.type)
        'radiology', 'health_organization', 'health_org', 'insurance', 'developer', 'lite',
        'other',
    ];

    public static function normalise(?string $type): string
    {
        return strtolower(str_replace([' ', '-'], '_', trim((string) $type)));
    }

    public static function label(?string $type): string
    {
        $slug = self::normalise($type);

        return $slug !== '' && Lang::has('finder_directory.types.' . $slug)
            ? __('finder_directory.types.' . $slug)
            : __('finder_directory.types.other');
    }
}
