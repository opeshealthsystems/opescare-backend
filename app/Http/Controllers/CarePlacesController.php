<?php

namespace App\Http\Controllers;

use App\Models\CareFacility;
use App\Support\CarePlaces;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

/**
 * Local-intent pages (/care-map/places, /care-map/{type}/{city}).
 * Every number on these pages is counted from active listings at request
 * time (combos are cached as arrays) — nothing is estimated or invented.
 */
class CarePlacesController extends Controller
{
    /** Listing rows rendered on one page; the rest are one click away in the directory. */
    public const LIST_LIMIT = 100;

    public function index(): View
    {
        $combos = CarePlaces::combos();

        $byCity = [];
        foreach ($combos as $combo) {
            $byCity[$combo['city_slug']]['city'] ??= $combo['city'];
            $byCity[$combo['city_slug']]['total'] = ($byCity[$combo['city_slug']]['total'] ?? 0) + $combo['count'];
            $byCity[$combo['city_slug']]['combos'][] = $combo;
        }
        uasort($byCity, fn ($a, $b) => $b['total'] <=> $a['total']);

        return view('care_map.places.index', [
            'byCity'     => $byCity,
            'comboCount' => count($combos),
        ]);
    }

    public function show(string $type, string $city): View
    {
        $combo = CarePlaces::find($type, $city);
        abort_if($combo === null, 404);

        $base = CareFacility::query()
            ->where('listing_status', 'active')
            ->where('facility_type', $combo['type'])
            ->whereIn(DB::raw('TRIM(city)'), $combo['cities']);

        $count = (clone $base)->count();
        abort_if($count < CarePlaces::MIN_LISTINGS, 404);   // the cache may lag a delisting

        $facilities = (clone $base)
            ->orderBy('facility_name')
            ->limit(self::LIST_LIMIT)
            ->get(['id', 'facility_name', 'facility_type', 'address', 'city', 'phone_primary', 'latitude', 'longitude']);

        $withPhone = (clone $base)
            ->whereNotNull('phone_primary')
            ->whereRaw("TRIM(phone_primary) <> ''")
            ->whereRaw('UPPER(TRIM(phone_primary)) <> ?', [CareFacility::PHONE_PLACEHOLDER])
            ->count();

        $claimed = (clone $base)->whereNotNull('claimed_by_user_id')->count();

        $bookable = (clone $base)
            ->whereNotNull('facility_id')
            ->whereIn('facility_id', DB::table('facilities')->whereIn('status', ['active', 'active_demo'])->select('id'))
            ->count();

        return view('care_map.places.show', [
            'combo'      => $combo,
            'count'      => $count,
            'facilities' => $facilities,
            'withPhone'  => $withPhone,
            'claimed'    => $claimed,
            'bookable'   => $bookable,
            'related'    => array_values(array_filter(
                CarePlaces::combos(),
                fn ($c) => $c['city_slug'] === $combo['city_slug'] && $c['type'] !== $combo['type']
            )),
        ]);
    }
}
