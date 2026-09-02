<?php

namespace App\Modules\CareMap\Services;

use App\Models\LabTestAvailability;

class LabTestSearchService
{
    /**
     * Search for labs that offer a particular test
     */
    public function searchTests($testName, $lat = null, $lon = null, $radius = 50)
    {
        $query = LabTestAvailability::where('test_name', 'like', "%{$testName}%")
            ->orWhere('loinc_code', 'like', "%{$testName}%")
            ->with('facility');

        $matches = $query->get();
        $facilities = [];

        foreach ($matches as $match) {
            $facility = $match->facility;
            
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
            $facility->matched_test = $match;
            $facilities[] = $facility;
        }

        usort($facilities, function ($a, $b) {
            if ($a->distance !== null && $b->distance !== null) {
                return $a->distance <=> $b->distance;
            }
            return strcmp($b->matched_test->freshness_status, $a->matched_test->freshness_status);
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
