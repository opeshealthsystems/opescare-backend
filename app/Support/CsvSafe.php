<?php

namespace App\Support;

/**
 * Neutralises spreadsheet formula injection (CSV injection, OWASP ASVS 5.3.10)
 * in every exported cell.
 *
 * A text cell that begins with = + - @, a tab or a carriage return is executed
 * as a formula by Excel / LibreOffice / Sheets. Such cells are prefixed with a
 * single quote so they render as text. Genuine numbers (ints, floats and
 * numeric strings such as "-5") are left untouched so aggregates stay usable.
 */
final class CsvSafe
{
    /** @param  array<int|string, mixed>  $row */
    public static function row(array $row): array
    {
        return array_map([self::class, 'cell'], $row);
    }

    public static function cell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '' || is_numeric($value)) {
            return $value;
        }

        return in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'" . $value
            : $value;
    }
}
