<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Trigram (pg_trgm) GIN indexes for the finders' substring searches.
 *
 * Every public search (facility directory, drug finder, formulary) runs
 * `LOWER(col) LIKE '%term%'`. A leading wildcard cannot use a btree, so at
 * national volume each keystroke was a sequential scan. A GIN index on
 * lower(col) gin_trgm_ops serves exactly that predicate shape.
 *
 * Non-transactional so the indexes build CONCURRENTLY (no write lock on a live
 * table) and so a refused CREATE EXTENSION does not poison the transaction.
 * pg_trgm is a trusted extension (PG13+): any role with CREATE on the database
 * can enable it. If the role cannot, the migration logs a warning and skips —
 * search stays correct, only slower.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    /** name => [table, expression] */
    private const INDEXES = [
        'care_facilities_name_trgm'        => ['care_facilities', 'lower(facility_name)'],
        'care_facilities_city_trgm'        => ['care_facilities', 'lower(city)'],
        'care_facilities_type_trgm'        => ['care_facilities', 'lower(facility_type)'],
        'care_facilities_description_trgm' => ['care_facilities', "lower(COALESCE(description, ''))"],
        'medicines_name_trgm'              => ['medicines', 'lower(name)'],
        'medicines_generic_name_trgm'      => ['medicines', 'lower(generic_name)'],
        'medicines_brand_name_trgm'        => ['medicines', "lower(COALESCE(brand_name, ''))"],
        'psa_medicine_name_trgm'           => ['pharmacy_stock_availability', 'lower(medicine_name)'],
        'psa_generic_name_trgm'            => ['pharmacy_stock_availability', "lower(COALESCE(generic_name, ''))"],
        'psa_brand_name_trgm'              => ['pharmacy_stock_availability', "lower(COALESCE(brand_name, ''))"],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (! $this->trigramAvailable()) {
            logger()->warning('finder trigram indexes skipped: pg_trgm cannot be enabled by this role; substring search falls back to sequential scans');

            return;
        }

        foreach (self::INDEXES as $name => [$table, $expr]) {
            DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS {$name} ON {$table} USING gin ({$expr} gin_trgm_ops)");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_keys(self::INDEXES) as $name) {
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS {$name}");
        }
    }

    private function trigramAvailable(): bool
    {
        if (DB::selectOne("SELECT 1 AS ok FROM pg_extension WHERE extname = 'pg_trgm'")) {
            return true;
        }

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

            return true;
        } catch (\Throwable $e) {
            logger()->warning('pg_trgm unavailable', ['error' => $e->getMessage()]);

            return false;
        }
    }
};
