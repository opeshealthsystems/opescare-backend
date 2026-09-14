<?php

namespace App\Services\CareMap;

use App\Models\CareFacility;
use App\Modules\CareMap\Services\FacilityListingEditService;
use Illuminate\Http\Request;

/**
 * The one place an opening-hours form is validated and turned into the week
 * that FacilityListingEditService::replaceHours() stores.
 *
 * Two screens post the same seven-day grid: the claimed-listing editor
 * (/portals/listing/hours, FacilityListingController) and the pharmacy's own
 * settings page (/pharmacy/settings/hours, PharmacySettingsController). They
 * resolve the listing differently — an approved claim versus the session
 * tenant — but the rows they write must be identical, so the validation and
 * the shaping live here and both controllers call this. Nothing about the
 * old route's behaviour changed when this was extracted.
 */
class ListingHoursService
{
    public function __construct(private readonly FacilityListingEditService $editor)
    {
    }

    /**
     * Validate the posted grid, replace the listing's week, return the number
     * of days that were stored. Throws a ValidationException like any
     * `$request->validate()` would, so the calling controller redirects back
     * with errors unchanged.
     */
    public function updateFromRequest(Request $request, CareFacility $listing, string $actorId): int
    {
        $request->validate([
            'hours'             => ['nullable', 'array'],
            'hours.*.opens_at'  => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i'],
        ]);

        return $this->editor->replaceHours($listing, $this->week($request), $actorId);
    }

    /**
     * The seven-day array replaceHours() expects, keyed 0..6 (Sunday first,
     * matching care_facility_hours.day_of_week).
     *
     * @return array<int,array{is_24_hours:bool,is_closed:bool,opens_at:?string,closes_at:?string}>
     */
    public function week(Request $request): array
    {
        $week = [];

        foreach (range(0, 6) as $day) {
            $spec = $request->input("hours.$day", []);

            $week[$day] = [
                'is_24_hours' => (bool) ($spec['is_24_hours'] ?? false),
                'is_closed'   => (bool) ($spec['is_closed'] ?? false),
                'opens_at'    => $spec['opens_at'] ?? null,
                'closes_at'   => $spec['closes_at'] ?? null,
            ];
        }

        return $week;
    }
}
