<?php

namespace App\Support;

/**
 * Locale-aware number formatting for user-facing counts and amounts.
 *
 * English: 2,737 and 1,234.5. French: 2 737 and 1 234,5 (a no-break space
 * groups thousands so a count never splits across lines). Use this instead of
 * bare number_format() anywhere a number is shown on a public or patient page.
 */
final class LocaleNumber
{
    public static function format(int|float|string|null $value, int $decimals = 0, ?string $locale = null): string
    {
        $locale = strtolower(substr($locale ?? app()->getLocale(), 0, 2));
        $value = (float) ($value ?? 0);

        return $locale === 'fr'
            ? number_format($value, $decimals, ',', "\u{00A0}")
            : number_format($value, $decimals, '.', ',');
    }
}
