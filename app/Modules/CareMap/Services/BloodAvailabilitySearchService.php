<?php

namespace App\Modules\CareMap\Services;

use App\Models\BloodAvailability;

/**
 * The public Blood Finder query.
 *
 * Feeds GET /api/v1/care-map/blood/search (CareMapController) and
 * GET /api/mobile/blood/search (MobileBloodController). Everything it returns
 * is shown to a patient — often one arranging an emergency transfusion — so
 * the provenance gate below is not optional.
 */
class BloodAvailabilitySearchService
{
    /**
     * Search for blood banks/hospitals with reported blood availability.
     *
     * @return list<\App\Models\CareFacility>
     */
    public function searchBlood($bloodGroup, $componentType = 'whole_blood', $lat = null, $lon = null, $radius = 50)
    {
        $query = BloodAvailability::query()
            ->where('blood_group', $bloodGroup)
            ->where('component_type', $componentType)
            ->available()
            /*
             * PROVENANCE GATE — do not remove.
             *
             * Withholds seeded and unattributed rows. `blood_availability` was
             * populated entirely by DemoBloodInventorySeeder, whose stock
             * levels are illustrative: no public source publishes live
             * Cameroonian blood inventory, so every number in it was invented
             * to make the screen render. Answering an O-negative search with
             * those rows tells somebody a hospital has units it may not have,
             * during the one search where being wrong costs the most.
             *
             * See App\Models\BloodAvailability::scopeReportedByRealSource() —
             * the same rule MedicinePharmacyStock applies to the medicine
             * finder. Rows written by BloodAvailabilityProjector from a real
             * blood bank's operational record carry a real source and pass.
             */
            ->reportedByRealSource()
            ->with('facility');

        $matches = $query->get();
        $facilities = [];

        foreach ($matches as $match) {
            $facility = $match->facility;

            // A listing deleted out from under an availability row has nothing
            // to show a patient — and dereferencing it here used to be a fatal.
            if ($facility === null) {
                continue;
            }

            if ($lat !== null && $lon !== null && $facility->latitude && $facility->longitude) {
                $distance = $this->calculateDistance($lat, $lon, $facility->latitude, $facility->longitude);
                if ($distance > $radius) {
                    continue;
                }
                $facility->distance = $distance;
            } else {
                $facility->distance = null;
            }

            $match->unsetRelation('facility');
            $facility->matched_blood = $match;
            $facilities[] = $facility;
        }

        usort($facilities, function ($a, $b) {
            if ($a->distance !== null && $b->distance !== null) {
                return $a->distance <=> $b->distance;
            }
            return strcmp($b->matched_blood->freshness_status, $a->matched_blood->freshness_status);
        });

        return $facilities;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
