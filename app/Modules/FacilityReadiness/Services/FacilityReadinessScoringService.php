<?php

namespace App\Modules\FacilityReadiness\Services;

use App\Models\FacilityReadinessScore;
use Illuminate\Support\Facades\DB;

/**
 * FacilityReadinessScoringService — Calculates and stores facility readiness scores.
 *
 * Imported from the platform (docs/PLATFORM_IMPORTS.md, group D) and ADAPTED to
 * the Finder: the Finder is a directory + care-operations layer, not an EMR, so
 * the platform's sub-scores (departments / schedules / price list relations,
 * DataCompletenessScore averages, GoLiveChecklist items) are replaced by the
 * checks that actually decide whether a facility tenant is findable and usable:
 *
 *  - listing_linked:   a care_facilities directory listing points at this tenant
 *  - coordinates_set:  that listing has latitude + longitude
 *  - hours_set:        that listing has at least one opening-hours row
 *  - catalogue_item:   at least one published catalogue / stock / lab-test item
 *  - staff_member:     at least one active staff member besides the admin
 *  - photo_set:        the listing has a photo (logo_path)
 *
 * Sub-scores keep the platform's four columns: staff_score (staff), config_score
 * (listing, coordinates, hours, photo), data_score (catalogue), support_score
 * (all automatic checks). As on the platform, is_ready is never set by the score:
 * go-live still requires an explicit approval (FacilityGoLiveService).
 */
class FacilityReadinessScoringService
{
    public const CHECKS = [
        'listing_linked',
        'coordinates_set',
        'hours_set',
        'catalogue_item',
        'staff_member',
        'photo_set',
    ];

    /**
     * Evaluate every check without writing anything (safe on a GET).
     *
     * @return array{checks: array<string,bool>, score: int, missing: list<string>}
     */
    public function evaluate(string $facilityId): array
    {
        $listing = DB::table('care_facilities')
            ->where('facility_id', $facilityId)
            ->orderByDesc('updated_at')
            ->first();

        $listingIds = DB::table('care_facilities')->where('facility_id', $facilityId)->pluck('id')->all();

        $checks = [
            'listing_linked'  => $listing !== null,
            'coordinates_set' => $listing !== null && $listing->latitude !== null && $listing->longitude !== null,
            'hours_set'       => $listingIds !== [] && DB::table('care_facility_hours')->whereIn('facility_id', $listingIds)->exists(),
            'catalogue_item'  => $this->hasPublishedItem($facilityId, $listingIds),
            'staff_member'    => $this->hasStaffBesidesAdmin($facilityId),
            'photo_set'       => $listing !== null && ! empty($listing->logo_path),
        ];

        $passed = count(array_filter($checks));

        return [
            'checks'  => $checks,
            'score'   => (int) round(($passed / count($checks)) * 100),
            'missing' => array_keys(array_filter($checks, fn (bool $ok) => ! $ok)),
        ];
    }

    public function calculateAndStore(string $facilityId, ?string $computedBy = null): FacilityReadinessScore
    {
        $result = $this->evaluate($facilityId);
        $c = $result['checks'];

        $pct = fn (array $items) => (int) round(count(array_filter($items)) / max(1, count($items)) * 100);

        return FacilityReadinessScore::create([
            'facility_id'   => $facilityId,
            'overall_score' => $result['score'],
            'staff_score'   => $pct([$c['staff_member']]),
            'config_score'  => $pct([$c['listing_linked'], $c['coordinates_set'], $c['hours_set'], $c['photo_set']]),
            'data_score'    => $pct([$c['catalogue_item']]),
            'support_score' => $result['score'],
            'open_blockers' => $result['missing'],
            'is_ready'      => false, // Requires explicit approval even at 100
            'calculated_at' => now(),
        ]);
    }

    /** @param list<string> $listingIds */
    private function hasPublishedItem(string $facilityId, array $listingIds): bool
    {
        if (DB::table('facility_catalogue_items')->where('facility_id', $facilityId)->where('is_active', true)->exists()) {
            return true;
        }

        if ($listingIds === []) {
            return false;
        }

        return DB::table('pharmacy_stock_availability')->whereIn('facility_id', $listingIds)->exists()
            || DB::table('lab_test_availability')->whereIn('facility_id', $listingIds)->exists();
    }

    /** Active postings (same rule as FacilityAdminDeskService::staffByRole) held by someone in a non-admin role. */
    private function hasStaffBesidesAdmin(string $facilityId): bool
    {
        return DB::table('facility_role_assignments')
            ->join('roles', 'roles.id', '=', 'facility_role_assignments.role_id')
            ->where('facility_role_assignments.facility_id', $facilityId)
            ->where('facility_role_assignments.is_active', true)
            ->where(fn ($q) => $q->whereNull('facility_role_assignments.expires_at')->orWhere('facility_role_assignments.expires_at', '>', now()))
            ->where('roles.name', 'not like', '%admin')
            ->exists();
    }
}
