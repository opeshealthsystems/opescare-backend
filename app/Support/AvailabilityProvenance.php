<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Availability provenance is an ALLOW-LIST.
 *
 * Each availability model declares `LIVE_SOURCE_SYSTEMS`: the only
 * `source_system` values a patient-facing read may publish. Anything else —
 * NULL, a seeder stamp, a value some future writer invents — is withheld.
 * An empty finder is correct; an unknown source is NOT live until someone
 * adds it here on purpose, with the writer that justifies it.
 *
 * An entry ending in ':*' is a namespaced family: 'partner_api:*' admits
 * 'partner_api:<integration_client_id>' (PartnerStockIngestService::sourceFor).
 * The bare prefix must be listed separately if it is live too.
 *
 * Guarded by tests/Feature/Architecture/AvailabilityProvenanceAllowListTest.
 */
final class AvailabilityProvenance
{
    /**
     * Seeder stamps. Never live, never allowed on any LIVE_SOURCE_SYSTEMS.
     * Kept only because seeders/coverage/takeover-review code needs to name them.
     */
    public const SYNTHETIC = ['demo_seed', 'seed', 'scale_synthetic'];

    /** @param  list<string>  $live */
    public static function isLive(?string $source, array $live): bool
    {
        if ($source === null || $source === '') {
            return false;
        }

        foreach ($live as $allowed) {
            if (str_ends_with($allowed, ':*')) {
                $prefix = substr($allowed, 0, -1); // keep the ':'
                if (str_starts_with($source, $prefix) && strlen($source) > strlen($prefix)) {
                    return true;
                }
            } elseif ($source === $allowed) {
                return true;
            }
        }

        return false;
    }

    /** @param  list<string>  $live */
    public static function constrain(Builder|QueryBuilder $query, string $column, array $live): Builder|QueryBuilder
    {
        $exact    = array_values(array_filter($live, fn ($v) => ! str_ends_with($v, ':*')));
        $prefixes = array_values(array_map(
            fn ($v) => substr($v, 0, -1),
            array_filter($live, fn ($v) => str_ends_with($v, ':*')),
        ));

        if ($exact === [] && $prefixes === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($column, $exact, $prefixes) {
            if ($exact !== []) {
                $q->whereIn($column, $exact);
            }
            foreach ($prefixes as $prefix) {
                // LEFT(), not LIKE: '_' in 'partner_api' is a LIKE wildcard.
                $q->orWhereRaw(
                    'LEFT(' . $column . ', ?) = ? AND LENGTH(' . $column . ') > ?',
                    [strlen($prefix), $prefix, strlen($prefix)],
                );
            }
        });
    }
}
