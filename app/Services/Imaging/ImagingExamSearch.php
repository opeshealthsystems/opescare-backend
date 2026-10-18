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
 * ICHI (added 2026-10-13, I-3): a diagnostic centre may also list its imaging
 * procedures as WHO ICHI interventions. ICHI codes its action axis in the
 * second segment, and action `BA` is "Diagnostic imaging" (every
 * radiography, ultrasound, CT, MRI, mammography and scintigraphy stem code
 * carries it; nothing else does). LabCatalogueProjector publishes those rows
 * into the same table and this reads them through the same gates. The rule
 * is the code's own structure, never a title.
 *
 * Not a patient search. Never reads or gates on payment.
 */
class ImagingExamSearch
{
    public const MAX_ROWS = 200;

    public const IMAGING_REASON_PREFIX = 'Imaging:';

    /** The catalogue `system` of WHO ICHI interventions. */
    public const ICHI_SYSTEM = 'ICHI';

    /** ICHI action axis value "Diagnostic imaging" (the second code segment). */
    public const ICHI_IMAGING_ACTION = 'BA';

    /** SQL LIKE pattern for an ICHI stem code whose action is diagnostic imaging. */
    public const ICHI_IMAGING_CODE_LIKE = '___.' . self::ICHI_IMAGING_ACTION . '.%';

    /** Whether an ICHI stem code is a diagnostic-imaging procedure (AAA.BA.AA). */
    public static function isImagingProcedureCode(?string $code): bool
    {
        return $code !== null
            && preg_match('/^[A-Z]{3}\.' . self::ICHI_IMAGING_ACTION . '\.[A-Z0-9]{2}$/', $code) === 1;
    }

    /**
     * The dictionary codes that reach the imaging finder: LOINC terms the
     * dictionary files as imaging, plus ICHI diagnostic-imaging stems. One
     * subquery, shared by the lab finder's exclusion, the catalogue mix and
     * the publication counts, so every reader draws the same line.
     */
    public static function imagingCodesQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('clinical_code_dictionary')
            ->select('code')
            ->where(function ($q) {
                $q->where(function ($loinc) {
                    $loinc->where('system', 'LOINC')
                        ->where('common_reason', 'like', self::IMAGING_REASON_PREFIX . '%');
                })->orWhere(function ($ichi) {
                    $ichi->where('system', self::ICHI_SYSTEM)
                        ->where('code', 'like', self::ICHI_IMAGING_CODE_LIKE);
                });
            });
    }

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

        $imagingCodes = self::imagingCodesQuery();

        $matchingCodes = self::imagingCodesQuery()
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
