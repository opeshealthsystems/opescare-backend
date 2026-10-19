<?php

namespace App\Support\Geo;

use Illuminate\Contracts\Database\Query\Builder as QueryBuilderContract;

/**
 * A lat/lng rectangle that fully contains a circle of `radiusKm` around a
 * point. Used as an index-friendly prefilter (`latitude BETWEEN .. AND
 * longitude BETWEEN ..`, served by the btree composite on (latitude,
 * longitude)) BEFORE any per-row Haversine, so a national directory never
 * computes trig over every row. The box is a superset of the circle: the exact
 * distance check still runs afterwards, so results are unchanged.
 */
final class BoundingBox
{
    private function __construct(
        public readonly float $minLat,
        public readonly float $maxLat,
        public readonly float $minLng,
        public readonly float $maxLng,
    ) {
    }

    public static function around(float $lat, float $lng, float $radiusKm): self
    {
        $radiusKm = max($radiusKm, 0.0);
        // 1 degree of latitude ~ 111.32 km everywhere; a degree of longitude
        // shrinks by cos(latitude). Guard the cosine near the poles.
        $latDelta = $radiusKm / 111.32;
        $cosLat   = max(cos(deg2rad($lat)), 0.0001);
        $lngDelta = min($radiusKm / (111.32 * $cosLat), 180.0);

        return new self($lat - $latDelta, $lat + $latDelta, $lng - $lngDelta, $lng + $lngDelta);
    }

    /** @template T of QueryBuilderContract @param T $query @return T */
    public function apply($query, string $latColumn = 'latitude', string $lngColumn = 'longitude')
    {
        return $query
            ->whereBetween($latColumn, [$this->minLat, $this->maxLat])
            ->whereBetween($lngColumn, [$this->minLng, $this->maxLng]);
    }
}
