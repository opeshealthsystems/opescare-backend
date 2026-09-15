<?php

namespace App\Services\Imaging;

use App\Models\LabTestAvailability;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ImagingExamSearch — "which imaging centre publishes this exam, at what price".
 *
 * No new schema. Imaging studies are LOINC codes (the RSNA Radiology Playbook
 * is part of LOINC; see ImportImagingCodes), so an imaging centre that adds
 * "Echographie abdominale" to /facility/catalogue is ALREADY projected by
 * LabCatalogueProjector into `lab_test_availability`. This reads those same
 * rows, through the same provenance gates as the lab finder
 * (reportedByRealSource + available + active listing), and keeps only codes
 * the dictionary files as imaging (`common_reason` starting "Imaging:").
 *
 * Honest limit: a code first imported by the lab sweep keeps its lab
 * `common_reason` (first sighting wins) and is not recognised as imaging
 * here. Nothing is guessed from a title.
 *
 * Not a patient search. Never reads or gates on payment.
 */
class ImagingExamSearch
{
    public const MAX_ROWS = 200;

    public const IMAGING_REASON_PREFIX = 'Imaging:';

    /**
     * @return Collection<int, \App\Models\CareFacility>  each with ->matched_test and ->distance
     */
    public function search(string $term, ?float $lat = null, ?float $lng = null, float $radiusKm = 50): Collection
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return collect();
        }

        $like = '%' . addcslashes($term, '%_\\') . '%';

        $imagingCodes = DB::table('clinical_code_dictionary')
            ->select('code')
            ->where('system', 'LOINC')
            ->where('common_reason', 'like', self::IMAGING_REASON_PREFIX . '%');

        $matchingCodes = DB::table('clinical_code_dictionary')
            ->select('code')
            ->where('system', 'LOINC')
            ->where('common_reason', 'like', self::IMAGING_REASON_PREFIX . '%')
            ->where(function ($q) use ($like) {
                $q->where('display', 'ilike', $like)
                    ->orWhere('display_fr', 'ilike', $like)
                    ->orWhere('common_reason', 'ilike', $like);
            });

        $rows = LabTestAvailability::query()
            ->reportedByRealSource()
            ->available()
            ->whereIn('loinc_code', $imagingCodes)
            ->where(function ($q) use ($like, $matchingCodes) {
                $q->where('test_name', 'ilike', $like)
                    ->orWhere('loinc_code', 'ilike', $like)
                    ->orWhereIn('loinc_code', $matchingCodes);
            })
            ->with('facility')
            ->orderByDesc('last_updated_at')
            ->limit(self::MAX_ROWS)
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $facility = $row->facility;
            if (! $facility || $facility->listing_status !== 'active') {
                continue;
            }

            $facility->distance = null;
            if ($lat !== null && $lng !== null && $facility->latitude && $facility->longitude) {
                $distance = $this->distanceKm($lat, $lng, (float) $facility->latitude, (float) $facility->longitude);
                if ($distance > $radiusKm) {
                    continue;
                }
                $facility->distance = $distance;
            }

            $row->unsetRelation('facility');
            // A listing publishing several matching exams appears once per exam.
            $entry = clone $facility;
            $entry->matched_test = $row;
            $out[] = $entry;
        }

        usort($out, fn ($a, $b) => ($a->distance ?? INF) <=> ($b->distance ?? INF));

        return collect($out);
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
