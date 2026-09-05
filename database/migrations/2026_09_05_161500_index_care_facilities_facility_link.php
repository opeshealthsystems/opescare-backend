<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Index the link between the two facility registers.
 *
 * ── This migration adds NO data and changes NO row ────────────────────────
 * It deliberately does not backfill `care_facilities.facility_id`. 2 260 of
 * 2 728 directory listings have no operational tenant behind them, and the
 * only signal available to guess one would be a name/city similarity — which
 * is exactly the wrong basis: a wrong link attaches a real patient's care to
 * the wrong hospital. Linking a listing to a tenant stays a reviewed act
 * (facility claim / admin verification), not a migration.
 *
 * What it does add is the index that the directory endpoint now needs.
 * `MobileFacilityController::index()` gained
 *
 *     WHERE facility_id IS [NOT] NULL          (the ?connected filter)
 *     ORDER BY (facility_id IS NULL) ASC       (connected listings first)
 *
 * and `care_facilities` had no index on `facility_id` at all — the FK column
 * was added by 2026_05_25_000000_create_care_map_tables and Postgres, unlike
 * MySQL, does not create an index for a foreign key. A partial index is the
 * right shape here because the interesting set is the minority: 468 rows out
 * of 2 728, and it will stay the minority as the directory import grows
 * faster than sign-ups.
 *
 * `staff_profiles.facility_id` gets a plain index for the same reason: the
 * per-page clinician count groups on it, and its FK was likewise unindexed.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Raw SQL, not Blueprint: a partial index is a Postgres construct and
        // Blueprint has no expression for it. IF NOT EXISTS so a rerun (or a
        // parallel branch that adds the same index) is a no-op rather than a
        // failed migrate on the deploy gate.
        if (Schema::hasTable('care_facilities') && Schema::hasColumn('care_facilities', 'facility_id')) {
            DB::statement(
                'CREATE INDEX IF NOT EXISTS care_facilities_facility_id_linked_index
                 ON care_facilities (facility_id)
                 WHERE facility_id IS NOT NULL'
            );
        }

        if (Schema::hasTable('staff_profiles') && Schema::hasColumn('staff_profiles', 'facility_id')) {
            DB::statement(
                'CREATE INDEX IF NOT EXISTS staff_profiles_facility_id_index
                 ON staff_profiles (facility_id)'
            );
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS care_facilities_facility_id_linked_index');
        DB::statement('DROP INDEX IF EXISTS staff_profiles_facility_id_index');
    }
};
