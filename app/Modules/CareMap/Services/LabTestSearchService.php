<?php

namespace App\Modules\CareMap\Services;

use App\Models\LabTestAvailability;

/**
 * The public lab finder: "which laboratories perform this test, and for how
 * much".
 *
 * Reads `lab_test_availability`, which is a projection of each facility's
 * service catalogue (App\Services\Lab\LabCatalogueProjector). Two gates run
 * on every search, before anything else:
 *
 *   reportedByRealSource()  only rows a real source published (the facility
 *                           portal). Seeded rows and rows with no provenance
 *                           are not evidence a lab performs a test, and this
 *                           is the answer a patient travels on.
 *   available()             a retired catalogue item keeps its row (the price
 *                           a patient was quoted stays accountable) but is no
 *                           longer offered.
 *
 * Name, city, coordinates come from the `care_facilities` listing the row is
 * keyed on. `matched_test.price_xaf` is whole francs; NULL means "no price
 * published", which is not the same as free.
 *
 * Not a patient search: the LIKE runs on test names and LOINC codes, which
 * their publishers publish in full. It is bounded (MAX_ROWS) all the same.
 */
class LabTestSearchService
{
    /** Hard ceiling on matched rows before distance filtering. */
    public const MAX_ROWS = 300;

    /**
     * Search for labs that offer a particular test
     */
    public function searchTests($testName, $lat = null, $lon = null, $radius = 50)
    {
        $term = trim((string) $testName);

        if ($term === '') {
            return [];
        }

        $like = '%' . addcslashes($term, '%_\\') . '%';

        $query = LabTestAvailability::query()
            ->reportedByRealSource()
            ->available()
            // Imaging exams publish through the same table but belong on the
            // imaging finder (docs/audits/IMAGING_DASHBOARD_AUDIT_2.md I-2,
            // DIAGNOSTIC_CENTRE D-1). The rule lives on the model so the lab
            // manager's "published" count applies exactly the same exclusion.
            ->excludingImaging()
            ->where(function ($q) use ($like) {
                $q->where('test_name', 'ilike', $like)
                    ->orWhere('loinc_code', 'ilike', $like);
            })
            ->with('facility')
            ->orderByDesc('last_updated_at')
            ->limit(self::MAX_ROWS);

        $matches = $query->get();
        $facilities = [];

        foreach ($matches as $match) {
            $facility = $match->facility;

            // A row whose listing is gone, or not publicly listed, has no
            // place to send anyone to.
            if (! $facility || $facility->listing_status !== 'active') {
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
            $facility->matched_test = $match;
            $facilities[] = $facility;
        }

        usort($facilities, function ($a, $b) {
            if ($a->distance !== null && $b->distance !== null) {
                return $a->distance <=> $b->distance;
            }

            return strcmp((string) $b->matched_test->freshness_status, (string) $a->matched_test->freshness_status);
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
