<?php

namespace App\Services\Pharmacy\StockImport;

use Carbon\Carbon;

/**
 * Reads the numbers and dates pharmacies actually type: "1 200", "1.200",
 * "1500,00", "2 500 FCFA", "31/12/2027", "12/2027", "2027-12", an Excel
 * serial date. Anything it cannot read for certain is an error, never a guess.
 */
final class ValueParser
{
    /** A whole number of packs, or null when the cell cannot be read as one. */
    public static function wholeNumber(string $raw): ?int
    {
        $v = self::stripNumber($raw);
        if ($v === null) {
            return null;
        }
        if (preg_match('/^\d+$/', $v)) {
            return (int) $v;
        }
        // 1.200 / 1,200 as thousands; 12,00 / 12.0 as a whole decimal.
        if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $v)) {
            return (int) preg_replace('/[.,]/', '', $v);
        }
        if (preg_match('/^(\d+)[.,]0+$/', $v, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /** Whole francs (XAF has no minor unit). Null when unreadable or fractional. */
    public static function francs(string $raw): ?int
    {
        $v = preg_replace('/(?i)\s*(fcfa|xaf|cfa|f)\.?\s*$/', '', trim($raw));

        return self::wholeNumber((string) $v);
    }

    private static function stripNumber(string $raw): ?string
    {
        $v = str_replace(["\u{00A0}", "\u{202F}", ' ', "'"], '', trim($raw));

        return $v === '' || str_starts_with($v, '-') ? null : $v;
    }

    /** A Y-m-d date, or null. Month-only dates mean the END of that month (how packs print expiry). */
    public static function date(string $raw): ?string
    {
        $v = trim($raw);
        if ($v === '') {
            return null;
        }

        // Excel serial (only plausible years 1982–2119).
        if (preg_match('/^\d{5}(\.\d+)?$/', $v) && (float) $v > 30000 && (float) $v < 80000) {
            return Carbon::create(1899, 12, 30)->addDays((int) floor((float) $v))->toDateString();
        }

        $v = preg_replace('/\s.*$/', '', $v); // drop a time part

        $try = static function (int $y, int $m, int $d): ?string {
            if ($y < 100) {
                $y += 2000;
            }

            return checkdate($m, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $m, $d) : null;
        };
        $endOfMonth = static function (int $y, int $m): ?string {
            if ($y < 100) {
                $y += 2000;
            }

            return $m >= 1 && $m <= 12 ? Carbon::create($y, $m, 1)->endOfMonth()->toDateString() : null;
        };

        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $v, $m)) {
            return $try((int) $m[1], (int) $m[2], (int) $m[3]);
        }
        if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2}|\d{4})$/', $v, $m)) {
            return $try((int) $m[3], (int) $m[2], (int) $m[1]); // day first: Cameroon writes 31/12/2027
        }
        if (preg_match('/^(\d{4})[-\/.](\d{1,2})$/', $v, $m)) {
            return $endOfMonth((int) $m[1], (int) $m[2]);
        }
        if (preg_match('/^(\d{1,2})[-\/.](\d{2}|\d{4})$/', $v, $m)) {
            return $endOfMonth((int) $m[2], (int) $m[1]);
        }

        return null;
    }
}
