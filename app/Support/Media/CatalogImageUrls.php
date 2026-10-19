<?php

namespace App\Support\Media;

use App\Models\CareFacility;
use App\Models\Medicine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Absolute, signed, cache-friendly image links for the two catalogue pictures
 * the app shows: a facility's logo and a medicine's image.
 *
 * ── Why signed ───────────────────────────────────────────────────────────
 * Both files sit on the private disk. An <Image> tag cannot carry a bearer
 * token, so the signature is the authorisation (same contract as the patient
 * photo and the settings-page facility photo).
 *
 * ── Why the expiry is bucketed ───────────────────────────────────────────
 * A plain `now()->addMinutes(60)` mints a different URL on every request, so
 * no client could ever reuse a cached copy. The expiry is instead pinned to
 * the end of the NEXT whole hour: the link is byte-identical for every request
 * inside the same hour (cache hit), and is always valid for at least 60
 * minutes after it was handed out. The `v` segment changes with each upload,
 * so a new picture never hides behind an old cached one.
 *
 * ── Never invent ─────────────────────────────────────────────────────────
 * No path → null. The client falls back to its icon; nothing here ever points
 * at a placeholder or a stock picture.
 */
final class CatalogImageUrls
{
    /** The link is valid until the end of the hour after the current one. */
    public static function expiry(): Carbon
    {
        return now()->startOfHour()->addHours(2);
    }

    public static function facilityLogo(?CareFacility $listing): ?string
    {
        if (! $listing || ! $listing->id || ! $listing->logo_path) {
            return null;
        }

        return URL::temporarySignedRoute('facility.photo', self::expiry(), [
            'listing' => $listing->id,
            'v'       => optional($listing->last_profile_update_at)->timestamp ?? 0,
        ]);
    }

    public static function medicineImage(?Medicine $medicine): ?string
    {
        if (! $medicine || ! $medicine->id || ! $medicine->image_path) {
            return null;
        }

        return URL::temporarySignedRoute('mobile.medicine.image', self::expiry(), [
            'medicine' => $medicine->id,
            'v'        => optional($medicine->updated_at)->timestamp ?? 0,
        ]);
    }

    /**
     * Logo links keyed by the TENANT facility id (`facilities.id`), for
     * payloads that only know the tenant facility (appointments). One query
     * for the whole list; a tenant facility with no linked, logo-bearing
     * directory listing simply has no entry.
     *
     * @param  array<int, string|null>  $facilityIds
     * @return array<string, string>
     */
    public static function facilityLogosByTenantIds(array $facilityIds): array
    {
        $ids = array_values(array_unique(array_filter($facilityIds)));
        if ($ids === []) {
            return [];
        }

        $out = [];
        CareFacility::query()
            ->whereIn('facility_id', $ids)
            ->whereNotNull('logo_path')
            ->orderByRaw("CASE WHEN listing_status = 'active' THEN 0 ELSE 1 END")
            ->get(['id', 'facility_id', 'logo_path', 'last_profile_update_at'])
            ->each(function (CareFacility $row) use (&$out): void {
                if (! isset($out[$row->facility_id])) {
                    $out[$row->facility_id] = (string) self::facilityLogo($row);
                }
            });

        return $out;
    }
}
