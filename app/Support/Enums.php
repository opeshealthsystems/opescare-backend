<?php

namespace App\Support;

class Enums
{
    /**
     * Translate a raw DB enum/status value into a localised, human-readable label.
     *
     * Looks up lang/{locale}/enums.php under the given group. When no translation
     * key exists the value is title-cased (`partially_paid` -> "Partially paid"),
     * so unknown / newly-added enum values degrade gracefully instead of leaking
     * raw snake_case or breaking the UI. Null / empty renders an em-dash.
     *
     * Usage (Blade): @enum($order->status)  @enum($rule->severity, 'severity')
     */
    public static function label(int|string|null $value, string $group = 'status'): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $value = (string) $value;
        $key   = 'enums.' . $group . '.' . str_replace('-', '_', $value);
        $label = __($key);

        if (is_string($label) && $label !== $key) {
            return $label;
        }

        return ucfirst(str_replace(['_', '-'], ' ', $value));
    }
}
