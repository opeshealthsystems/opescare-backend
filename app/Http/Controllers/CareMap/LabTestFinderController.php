<?php

namespace App\Http\Controllers\CareMap;

use App\Http\Controllers\Controller;
use App\Modules\CareMap\Services\LabTestSearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The public lab-test finder page (/network/lab-test-finder).
 *
 * The JSON API (GET /api/v1/care-map/labs/test-search) existed and no web
 * page used it: a patient on the website could find a medicine or a blood
 * unit but not a laboratory test. This page renders server-side through the
 * SAME LabTestSearchService, so its provenance gates (portal-published rows
 * only, retired tests hidden, active listings only) are the API's gates —
 * the page and the API cannot disagree.
 *
 * Not a patient search: it matches published test names and LOINC codes.
 * Nothing here reads or gates on payment.
 */
class LabTestFinderController extends Controller
{
    public function __construct(private readonly LabTestSearchService $search)
    {
    }

    public function __invoke(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $query = mb_substr($query, 0, 120);

        [$lat, $lng] = $this->coordinates($request);

        $results = null;
        if (mb_strlen($query) >= 2) {
            $results = collect($this->search->searchTests($query, $lat, $lng, 50));
        }

        return view('care_map.labs.test_finder', [
            'query'   => $query,
            'lat'     => $lat,
            'lng'     => $lng,
            'results' => $results,
        ]);
    }

    /**
     * Both coordinates numeric and plausible, or neither. Garbage in the
     * query string never reaches the distance formula.
     *
     * @return array{0: float|null, 1: float|null}
     */
    private function coordinates(Request $request): array
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return [null, null];
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return [null, null];
        }

        return [$lat, $lng];
    }
}
