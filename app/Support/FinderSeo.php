<?php

namespace App\Support;

use App\Models\CareFacility;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Resolves the localized meta layer for OpesCare Finder pages.
 *
 * The layout contract stays @yield('title') / @yield('description') — pages
 * that set their own sections always win. These helpers are the FALLBACK the
 * layout can hand to @yield's second argument, resolved per route name via
 * config/finder-seo.php → lang/{en,fr}/finder_seo.php, so no public page can
 * ship with the generic site blurb by accident.
 *
 * Facility pages are the special case worth centralizing: the title carries
 * the city and region ("Pharmacie X — Douala, Littoral | OpesCare Finder")
 * because "pharmacie douala" is the query this product exists to win.
 */
class FinderSeo
{
    /** Localized page title for the current (or given) route name. */
    public static function title(?string $routeName = null): string
    {
        $key = self::pageKey($routeName);

        return $key !== null
            ? __("finder_seo.pages.{$key}.title")
            : __('finder_seo.site.title');
    }

    /** Localized meta description for the current (or given) route name. */
    public static function description(?string $routeName = null): string
    {
        $key = self::pageKey($routeName);

        $text = $key !== null
            ? __("finder_seo.pages.{$key}.description")
            : __('finder_seo.site.description');

        return Str::limit($text, 155, '');
    }

    /**
     * Profile-page title with the locality in it — the GEO signal.
     * "Name — City, Region | OpesCare Finder", degrading gracefully when the
     * registry row has no city.
     */
    public static function facilityTitle(CareFacility $facility): string
    {
        $city   = CareFacility::realValue($facility->city);
        $region = CareFacility::realValue($facility->region);

        if ($city !== null && $region !== null && strcasecmp($city, $region) !== 0) {
            return __('finder_seo.profile.title', [
                'name' => $facility->facility_name, 'city' => $city, 'region' => $region,
            ]);
        }

        if ($city !== null || $region !== null) {
            return __('finder_seo.profile.title_city', [
                'name' => $facility->facility_name, 'city' => $city ?? $region,
            ]);
        }

        return __('finder_seo.profile.title_bare', ['name' => $facility->facility_name]);
    }

    /** Profile-page meta description, ≤155 chars, locality words included. */
    public static function facilityDescription(CareFacility $facility): string
    {
        $city = CareFacility::realValue($facility->city);
        $type = self::facilityTypeLabel($facility->facility_type);

        $text = $city !== null
            ? __('finder_seo.profile.description', [
                'name' => $facility->facility_name, 'type' => $type, 'city' => $city,
            ])
            : __('finder_seo.profile.description_bare', [
                'name' => $facility->facility_name, 'type' => $type,
            ]);

        return Str::limit($text, 155, '');
    }

    /** Localized human label for a facility_type value. */
    public static function facilityTypeLabel(?string $type): string
    {
        $type = strtolower(str_replace(' ', '_', (string) $type));
        $key  = "finder_seo.types.{$type}";
        $label = __($key);

        // Unknown type → the generic label, never a raw lang key on a page.
        return $label === $key ? __('finder_seo.types.facility') : $label;
    }

    private static function pageKey(?string $routeName): ?string
    {
        $routeName ??= optional(Route::current())->getName();

        if ($routeName === null) {
            return null;
        }

        return config("finder-seo.routes.{$routeName}");
    }
}
