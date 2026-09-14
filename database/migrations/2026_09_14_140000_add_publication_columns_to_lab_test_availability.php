<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `lab_test_availability` becomes a PROJECTION of the facility service
 * catalogue (docs/LAB_DASHBOARD_AUDIT.md, L-7).
 *
 * The public lab finder read this table and nothing wrote it, while the lab
 * manager curated `facility_catalogue_items` — with a price — that reached no
 * patient. Three columns make the table honest about where a row came from:
 *
 *   price_xaf          whole francs, copied from the catalogue. XAF has no
 *                      minor unit; the legacy `price` decimal(10,2) stays for
 *                      readers that still expect it and is written the same
 *                      value.
 *   source_system      'portal' when published from the catalogue. NULL and
 *                      'seed'/'demo_seed' rows are withheld from the public
 *                      search (LabTestAvailability::scopeReportedByRealSource),
 *                      exactly like medicine_pharmacy_stocks.
 *   last_published_at  when the projector last wrote the row.
 *
 * One published row per (listing, LOINC code): the partial unique index is
 * the guarantee a re-sync can never duplicate a test. Legacy rows with no
 * LOINC code are left alone (NULLs are outside the index).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_test_availability', function (Blueprint $table) {
            if (! Schema::hasColumn('lab_test_availability', 'price_xaf')) {
                $table->unsignedInteger('price_xaf')->nullable()->after('price');
            }
            if (! Schema::hasColumn('lab_test_availability', 'source_system')) {
                $table->string('source_system', 40)->nullable()->after('freshness_status');
            }
            if (! Schema::hasColumn('lab_test_availability', 'last_published_at')) {
                $table->timestamp('last_published_at')->nullable()->after('last_updated_at');
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS lab_test_availability_listing_loinc_unique
                ON lab_test_availability (facility_id, loinc_code) WHERE loinc_code IS NOT NULL');
            DB::statement('CREATE INDEX IF NOT EXISTS lab_test_availability_source_system_index
                ON lab_test_availability (source_system)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS lab_test_availability_listing_loinc_unique');
            DB::statement('DROP INDEX IF EXISTS lab_test_availability_source_system_index');
        }

        Schema::table('lab_test_availability', function (Blueprint $table) {
            $table->dropColumn(['price_xaf', 'source_system', 'last_published_at']);
        });
    }
};
