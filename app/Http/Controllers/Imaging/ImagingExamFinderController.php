<?php

namespace App\Http\Controllers\Imaging;

use App\Http\Controllers\Controller;
use App\Services\Imaging\ImagingExamSearch;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public imaging-exam finder (/network/imaging-finder).
 *
 * Server-rendered over ImagingExamSearch, which reads the rows imaging
 * centres already publish from their catalogue. Search is over published
 * exam names and LOINC codes only — never patients.
 */
class ImagingExamFinderController extends Controller
{
    public function __construct(private readonly ImagingExamSearch $search)
    {
    }

    public function __invoke(Request $request): View
    {
        $query = mb_substr(trim((string) $request->query('q', '')), 0, 120);

        $lat = $request->query('lat');
        $lng = $request->query('lng');
        if (! is_numeric($lat) || ! is_numeric($lng)
            || (float) $lat < -90 || (float) $lat > 90 || (float) $lng < -180 || (float) $lng > 180) {
            $lat = $lng = null;
        } else {
            $lat = (float) $lat;
            $lng = (float) $lng;
        }

        $results = mb_strlen($query) >= 2 ? $this->search->search($query, $lat, $lng) : null;

        return view('public.imaging.finder', compact('query', 'lat', 'lng', 'results'));
    }
}
