<?php

namespace App\Support;

class Enums
{
    /** Call-site group names that live under a different key in lang/{locale}/enums.php. */
    private const GROUP_ALIASES = [
        'route' => 'medication_route',
    ];

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
        $slug  = str_replace('-', '_', $value);

        // Try the named group first, then its known alias, then the shared
        // `status` group (most raw values are statuses), so French gets
        // French whenever any key exists. The title-case wash is last resort.
        $groups = array_unique(array_filter([
            $group,
            self::GROUP_ALIASES[$group] ?? null,
            'status',
        ]));

        foreach ($groups as $g) {
            $key   = 'enums.' . $g . '.' . $slug;
            $label = __($key);

            if (is_string($label) && $label !== $key) {
                return $label;
            }
        }

        return ucfirst(str_replace(['_', '-'], ' ', $value));
    }
}
