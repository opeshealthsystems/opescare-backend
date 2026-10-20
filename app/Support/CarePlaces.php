<?php

namespace App\Support;

use App\Models\CareFacility;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Local-intent landing pages: "Pharmacies in Yaoundé", "Hospitals in Douala".
 *
 * One combo = one directory facility type × one city, built from the ACTIVE
 * care_facilities listings only. A combo exists only with at least MIN_LISTINGS
 * active listings, so no thin page is ever generated — anything below that
 * 404s. Cities are folded by an ASCII slug, so "Kousséri" and "Kousseri" (or
 * "DOUALA" and "Douala") are one page; the most common spelling is shown.
 *
 * Cached as plain arrays (never models or Carbon — see never-cache-objects).
 */
final class CarePlaces
{
    public const MIN_LISTINGS = 3;

    public const CACHE_KEY = 'care-places.combos.v1';

    /** Directory (care_facilities) types that may get a place page. */
    public const TYPES = [
        'hospital', 'clinic', 'pharmacy', 'health_center', 'laboratory', 'dental',
        'imaging_center', 'eye_clinic', 'specialist', 'diagnostic_center', 'maternity',
        'dispensary', 'blood_bank', 'nursing_home',
    ];

    public static function typeSlug(string $type): string
    {
        return str_replace('_', '-', $type);
    }

    public static function typeFromSlug(string $slug): ?string
    {
        $type = str_replace('-', '_', strtolower($slug));

        return in_array($type, self::TYPES, true) ? $type : null;
    }

    /** Route constraint: only known type slugs, so /care-map/facility/{id} is never shadowed. */
    public static function typeSlugPattern(): string
    {
        return implode('|', array_map([self::class, 'typeSlug'], self::TYPES));
    }

    public static function citySlug(string $city): string
    {
        return Str::slug($city);
    }

    /**
     * Every qualifying combo, largest first.
     *
     * @return list<array{type:string,type_slug:string,city:string,city_slug:string,cities:list<string>,count:int}>
     */
    public static function combos(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, fn () => self::build());
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array{type:string,type_slug:string,city:string,city_slug:string,cities:list<string>,count:int}|null */
    public static function find(string $typeSlug, string $citySlug): ?array
    {
        foreach (self::combos() as $combo) {
            if ($combo['type_slug'] === strtolower($typeSlug) && $combo['city_slug'] === strtolower($citySlug)) {
                return $combo;
            }
        }

        return null;
    }

    /** Distinct cities that have at least one combo. */
    public static function cityCount(): int
    {
        return count(array_unique(array_column(self::combos(), 'city_slug')));
    }

    private static function build(): array
    {
        $rows = CareFacility::query()
            ->where('listing_status', 'active')
            ->whereIn('facility_type', self::TYPES)
            ->whereNotNull('city')
            ->selectRaw('facility_type, TRIM(city) AS city, COUNT(*) AS n')
            ->groupBy('facility_type')
            ->groupByRaw('TRIM(city)')
            ->get()
            ->map(fn ($r) => ['type' => (string) $r->facility_type, 'city' => (string) $r->city, 'n' => (int) $r->n])
            ->all();

        $groups = [];

        foreach ($rows as $row) {
            $city = CareFacility::realValue($row['city']);
            if ($city === null) {
                continue;
            }
            $slug = self::citySlug($city);
            if ($slug === '') {
                continue;
            }

            $key = $row['type'] . '|' . $slug;
            $groups[$key] ??= ['type' => $row['type'], 'city_slug' => $slug, 'variants' => [], 'count' => 0];
            $groups[$key]['variants'][$city] = ($groups[$key]['variants'][$city] ?? 0) + $row['n'];
            $groups[$key]['count'] += $row['n'];
        }

        $combos = [];

        foreach ($groups as $group) {
            if ($group['count'] < self::MIN_LISTINGS) {
                continue;
            }

            $variants = $group['variants'];
            arsort($variants);
            $display = (string) array_key_first($variants);
            if (mb_strtolower($display) === $display || mb_strtoupper($display) === $display) {
                $display = mb_convert_case(mb_strtolower($display), MB_CASE_TITLE, 'UTF-8');
            }

            $combos[] = [
                'type'      => $group['type'],
                'type_slug' => self::typeSlug($group['type']),
                'city'      => $display,
                'city_slug' => $group['city_slug'],
                'cities'    => array_keys($group['variants']),
                'count'     => $group['count'],
            ];
        }

        usort($combos, fn ($a, $b) => [$b['count'], $a['city_slug'], $a['type']] <=> [$a['count'], $b['city_slug'], $b['type']]);

        return $combos;
    }
}
