<?php

namespace App\Services\Billing;

use App\Services\Payments\Domain\Money;

/**
 * Whole francs, in and out of decimal(12,2) columns that predate the rule.
 *
 * The 2026-05 billing tables store amounts as decimal(12,2). XAF has ISO 4217
 * exponent 0 — there is no centime — so the desk only ever writes integers
 * into them and only ever reads integers back. Every arithmetic step in the
 * desk is on PHP ints; the decimal column is a container, not a number line.
 */
final class Xaf
{
    /** Read a decimal(12,2) value back as the whole francs it holds. */
    public static function int(mixed $stored): int
    {
        if ($stored === null || $stored === '') {
            return 0;
        }

        // The column may hand back "1500.00"; the value is 1500 francs.
        return (int) round((float) $stored);
    }

    /** "5 000 FCFA", or "0 FCFA" — Money refuses a zero, a receipt must not. */
    public static function format(int $francs): string
    {
        $sign   = $francs < 0 ? '-' : '';
        $digits = (string) abs($francs);

        // Same grouping as Money::format() (narrow no-break space), done here
        // because Money refuses a zero and a session total may exceed the
        // single-invoice ceiling Money enforces.
        $chunks = array_map('strrev', str_split(strrev($digits), 3));

        return $sign.implode("\u{202F}", array_reverse($chunks)).' '.Money::symbolFor('XAF');
    }
}
