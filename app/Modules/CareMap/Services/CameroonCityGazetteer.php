<?php

namespace App\Modules\CareMap\Services;

use Illuminate\Support\Str;

/**
 * A small gazetteer of Cameroonian towns, used to turn coordinates into the
 * `city` / `region` a directory row needs.
 *
 * WHY THIS EXISTS
 * ---------------
 * `care_facilities.city` is NOT NULL and `region` drives the facility_code
 * prefix and every regional filter in the portal. OpenStreetMap almost never
 * supplies either: of 255 health elements inside the Douala bounding box, five
 * carried `addr:city` and none carried a region. Without a gazetteer the
 * importer would have to invent a city, and a facility filed under the wrong
 * region is a facility a patient in that region will not find.
 *
 * The city names and regional spellings here match what is already in
 * `care_facilities` (mixed FR/EN, accented — 'Yaoundé', 'Extrême-Nord',
 * 'Ngaoundéré'), so imported rows group with the MINSANTE rows in every
 * existing GROUP BY rather than forming a parallel set of near-duplicates.
 *
 * Bounding boxes are derived from a centre and a radius rather than written out
 * by hand: forty hand-typed bboxes is forty chances to transpose a digit and
 * silently import the wrong half of a city.
 *
 * This is a coarse instrument by design. A point outside every town's radius
 * gets NO city — the importer sends it to human review rather than guessing.
 */
class CameroonCityGazetteer
{
    /**
     * name => [region, latitude, longitude, radius_km]
     *
     * Radius is "how far out this town's health facilities plausibly sit", not
     * an administrative boundary. Douala and Yaoundé are metropolitan and get
     * a wide one; the rest are towns.
     */
    private const CITIES = [
        'Douala'      => ['Littoral',     4.0500,  9.7000, 20],
        'Yaoundé'     => ['Centre',       3.8700, 11.5200, 20],
        'Bamenda'     => ['Nord-Ouest',   5.9600, 10.1500, 12],
        'Bafoussam'   => ['Ouest',        5.4800, 10.4200, 12],
        'Garoua'      => ['Nord',         9.3000, 13.3900, 12],
        'Maroua'      => ['Extrême-Nord', 10.5900, 14.3200, 12],
        'Ngaoundéré'  => ['Adamaoua',     7.3200, 13.5800, 12],
        'Bertoua'     => ['Est',          4.5800, 13.6800, 12],
        'Buea'        => ['Sud-Ouest',    4.1500,  9.2400, 10],
        'Limbe'       => ['Sud-Ouest',    4.0200,  9.2000, 10],
        'Kumba'       => ['Sud-Ouest',    4.6300,  9.4400, 10],
        'Tiko'        => ['Sud-Ouest',    4.0800,  9.3600,  8],
        'Kribi'       => ['Sud',          2.9400,  9.9100, 10],
        'Ebolowa'     => ['Sud',          2.9100, 11.1500, 10],
        'Sangmélima'  => ['Sud',          2.9300, 11.9800, 10],
        'Edéa'        => ['Littoral',     3.8000, 10.1300, 10],
        'Nkongsamba'  => ['Littoral',     4.9500,  9.9400, 10],
        'Mbanga'      => ['Littoral',     4.5100,  9.5700,  8],
        'Loum'        => ['Littoral',     4.7200,  9.7300,  8],
        'Dschang'     => ['Ouest',        5.4500, 10.0500, 10],
        'Foumban'     => ['Ouest',        5.7300, 10.9000, 10],
        'Bafang'      => ['Ouest',        5.1600, 10.1800,  8],
        'Mbouda'      => ['Ouest',        5.6300, 10.2500,  8],
        'Bangangté'   => ['Ouest',        5.1500, 10.5200,  8],
        'Bafia'       => ['Centre',       4.7500, 11.2300, 10],
        'Mbalmayo'    => ['Centre',       3.5200, 11.5000, 10],
        'Eseka'       => ['Centre',       3.6500, 10.7700,  8],
        'Akonolinga'  => ['Centre',       3.7700, 12.2500,  8],
        'Obala'       => ['Centre',       4.1700, 11.5300,  8],
        'Ayos'        => ['Centre',       3.9100, 12.5300,  8],
        'Kumbo'       => ['Nord-Ouest',   6.2000, 10.6700, 10],
        'Wum'         => ['Nord-Ouest',   6.3800, 10.0700,  8],
        'Batouri'     => ['Est',          4.4300, 14.3600,  8],
        'Abong-Mbang' => ['Est',          3.9800, 13.1800,  8],
        'Meiganga'    => ['Adamaoua',     6.5200, 14.2900,  8],
        'Tibati'      => ['Adamaoua',     6.4700, 12.6300,  8],
        'Banyo'       => ['Adamaoua',     6.7500, 11.8200,  8],
        'Tignere'     => ['Adamaoua',     7.3700, 12.6500,  8],
        'Guider'      => ['Nord',         9.9300, 13.9500,  8],
        'Kousséri'    => ['Extrême-Nord', 12.0800, 15.0300, 10],
        'Yagoua'      => ['Extrême-Nord', 10.3400, 15.2400,  8],
    ];

    /** @return list<string> */
    public function names(): array
    {
        return array_keys(self::CITIES);
    }

    /**
     * Resolve a user-typed --city value. Accent- and case-insensitive, so
     * `--city=yaounde` finds 'Yaoundé' from a keyboard that has no é.
     *
     * @return array{name: string, region: string, latitude: float, longitude: float, radius_km: int}|null
     */
    public function find(string $input): ?array
    {
        $needle = $this->fold($input);

        foreach (self::CITIES as $name => [$region, $lat, $lng, $radius]) {
            if ($this->fold($name) === $needle) {
                return [
                    'name'      => $name,
                    'region'    => $region,
                    'latitude'  => $lat,
                    'longitude' => $lng,
                    'radius_km' => $radius,
                ];
            }
        }

        return null;
    }

    /**
     * The nearest town whose radius actually contains this point.
     *
     * Returns null when the point sits between towns — rural facilities exist
     * and are worth having, but assigning one to the nearest big city 60 km
     * away would file it where nobody looking for it will search. The caller
     * routes a null to human review instead.
     *
     * @return array{name: string, region: string, distance_m: int}|null
     */
    public function nearest(float $latitude, float $longitude): ?array
    {
        $best = null;

        foreach (self::CITIES as $name => [$region, $lat, $lng, $radius]) {
            $distance = self::haversineMetres($latitude, $longitude, $lat, $lng);

            if ($distance > $radius * 1000) {
                continue;
            }

            if ($best === null || $distance < $best['distance_m']) {
                $best = ['name' => $name, 'region' => $region, 'distance_m' => (int) round($distance)];
            }
        }

        return $best ?? $this->nearestSettlement($latitude, $longitude);
    }

    /**
     * Fall back to the nearest OSM-mapped settlement.
     *
     * `care_facilities.city` is NOT NULL, and the curated list above covers 41
     * towns with radii. Every facility outside all of them was rejected as
     * unresolved — 249 of them on the first national import, overwhelmingly
     * rural, which is precisely the coverage the import exists to add.
     *
     * OSM maps 10,754 named settlements in Cameroon. Using the nearest one
     * gives a real, sourced town name rather than a fabricated or region-level
     * placeholder, under the same ODbL licence as the facilities themselves.
     * Towns and cities outrank villages within a tolerance, so a facility
     * resolves to the town it belongs to rather than a hamlet a few hundred
     * metres nearer.
     *
     * Beyond MAX_SETTLEMENT_KM there is genuinely nothing to name the place
     * after, and the caller should keep treating that as unresolved.
     */
    private const MAX_SETTLEMENT_KM = 30;

    /** @var list<array{name:string,region:string,lat:float,lon:float,rank:int}>|null */
    private static ?array $settlements = null;

    private function nearestSettlement(float $latitude, float $longitude): ?array
    {
        if (self::$settlements === null) {
            $path = resource_path('data/cameroon_settlements.json');
            $data = is_file($path)
                ? json_decode((string) file_get_contents($path), true)
                : null;
            self::$settlements = $data['places'] ?? [];
        }

        $best = null;
        $ceiling = self::MAX_SETTLEMENT_KM * 1000;

        foreach (self::$settlements as $s) {
            $distance = self::haversineMetres($latitude, $longitude, $s['lat'], $s['lon']);

            if ($distance > $ceiling) {
                continue;
            }

            // A town within 5km beats a nearer village; below that, distance wins.
            $score = $distance - (($s['rank'] ?? 1) - 1) * 5000;

            if ($best === null || $score < $best['score']) {
                $best = [
                    'name'       => $s['name'],
                    'region'     => $s['region'],
                    'distance_m' => (int) round($distance),
                    'score'      => $score,
                ];
            }
        }

        if ($best !== null) {
            unset($best['score']);
        }

        return $best;
    }

    /**
     * The Overpass bounding box for a city: south, west, north, east.
     *
     * Longitude degrees shrink towards the poles, so the east/west span is
     * divided by cos(latitude). Cameroon sits near the equator where that
     * correction is small, but getting it wrong the other way (a bbox too
     * narrow) would silently drop facilities on a city's edge.
     *
     * @param  array{latitude: float, longitude: float, radius_km: int} $city
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    public function boundingBox(array $city): array
    {
        $latDelta = $city['radius_km'] / 111.0;
        $lngDelta = $city['radius_km'] / (111.0 * max(0.1, cos(deg2rad($city['latitude']))));

        return [
            round($city['latitude'] - $latDelta, 5),
            round($city['longitude'] - $lngDelta, 5),
            round($city['latitude'] + $latDelta, 5),
            round($city['longitude'] + $lngDelta, 5),
        ];
    }

    /** Great-circle distance in metres. */
    public static function haversineMetres(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function fold(string $value): string
    {
        return Str::lower(trim(Str::ascii($value)));
    }
}
