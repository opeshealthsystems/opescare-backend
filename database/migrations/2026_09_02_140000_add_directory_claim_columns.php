<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes `facility_claims` able to describe the claim that actually happens:
 * a person who runs a facility finds *the directory listing* and claims it.
 *
 * Until now the table could not express that. `facility_claims.facility_id`
 * has a foreign key to `facilities` (the operational tenant), but the only
 * caller in the product — `CareMapController::claimFacility`, reached from the
 * public listing page — passes a `care_facilities.id`. 1,395 of the 1,863
 * directory rows have no operational Facility at all, so for three quarters of
 * the directory a claim could not be written even in principle; for the rest it
 * would have been written against the wrong row. The table holds 0 claims.
 *
 * So: `care_facility_id` names the listing being claimed, `facility_id` becomes
 * nullable (a listing may be claimed before any tenant exists), and the claim
 * carries the contact details a human reviewer needs in order to decide.
 *
 * On `care_facilities`, `claimed_by_user_id` / `claimed_at` record the outcome.
 * They are deliberately NOT `verification_status`: nothing in this directory is
 * verified, and someone asserting they run a hospital is not verification. The
 * two states are stored separately so the UI can never conflate them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('facility_claims')) {
            Schema::table('facility_claims', function (Blueprint $table) {
                if (! Schema::hasColumn('facility_claims', 'care_facility_id')) {
                    $table->uuid('care_facility_id')->nullable()->after('facility_id');
                }
                if (! Schema::hasColumn('facility_claims', 'claimant_name')) {
                    $table->string('claimant_name')->nullable()->after('claimant_user_id');
                }
                if (! Schema::hasColumn('facility_claims', 'claimant_role')) {
                    $table->string('claimant_role', 64)->nullable()->after('claimant_name');
                }
                if (! Schema::hasColumn('facility_claims', 'claimant_email')) {
                    $table->string('claimant_email')->nullable()->after('claimant_role');
                }
                if (! Schema::hasColumn('facility_claims', 'claimant_phone')) {
                    $table->string('claimant_phone', 40)->nullable()->after('claimant_email');
                }
            });

            // facility_id must be optional: most of the directory has no
            // operational Facility behind it, and a claim on such a listing is
            // exactly the case this feature exists to serve.
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE facility_claims ALTER COLUMN facility_id DROP NOT NULL');
                DB::statement("
                    DO $$
                    BEGIN
                        IF NOT EXISTS (
                            SELECT 1 FROM information_schema.table_constraints
                            WHERE table_name = 'facility_claims'
                              AND constraint_name = 'facility_claims_care_facility_id_foreign'
                              AND constraint_type = 'FOREIGN KEY'
                        ) THEN
                            ALTER TABLE facility_claims
                                ADD CONSTRAINT facility_claims_care_facility_id_foreign
                                FOREIGN KEY (care_facility_id) REFERENCES care_facilities(id) ON DELETE CASCADE;
                        END IF;
                    END
                    $$
                ");
                DB::statement(
                    'CREATE INDEX IF NOT EXISTS facility_claims_care_facility_id_index
                     ON facility_claims (care_facility_id)'
                );
                // One live claim per listing per claimant. A rejected claim may
                // be re-submitted; two open claims by the same person may not.
                DB::statement(
                    "CREATE UNIQUE INDEX IF NOT EXISTS facility_claims_open_claim_unique
                     ON facility_claims (care_facility_id, claimant_user_id)
                     WHERE care_facility_id IS NOT NULL
                       AND claim_status IN ('submitted', 'under_review', 'approved')"
                );
            }
        }

        if (Schema::hasTable('care_facilities')) {
            Schema::table('care_facilities', function (Blueprint $table) {
                if (! Schema::hasColumn('care_facilities', 'claimed_by_user_id')) {
                    $table->uuid('claimed_by_user_id')->nullable()->after('partner_id');
                }
                if (! Schema::hasColumn('care_facilities', 'claimed_at')) {
                    $table->timestamp('claimed_at')->nullable()->after('claimed_by_user_id');
                }
            });

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("
                    DO $$
                    BEGIN
                        IF NOT EXISTS (
                            SELECT 1 FROM information_schema.table_constraints
                            WHERE table_name = 'care_facilities'
                              AND constraint_name = 'care_facilities_claimed_by_user_id_foreign'
                              AND constraint_type = 'FOREIGN KEY'
                        ) THEN
                            ALTER TABLE care_facilities
                                ADD CONSTRAINT care_facilities_claimed_by_user_id_foreign
                                FOREIGN KEY (claimed_by_user_id) REFERENCES users(id) ON DELETE SET NULL;
                        END IF;
                    END
                    $$
                ");
                DB::statement(
                    'CREATE INDEX IF NOT EXISTS care_facilities_claimed_by_user_id_index
                     ON care_facilities (claimed_by_user_id)'
                );
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS facility_claims_open_claim_unique');
            DB::statement('DROP INDEX IF EXISTS facility_claims_care_facility_id_index');
            DB::statement('DROP INDEX IF EXISTS care_facilities_claimed_by_user_id_index');
            DB::statement('ALTER TABLE facility_claims DROP CONSTRAINT IF EXISTS facility_claims_care_facility_id_foreign');
            DB::statement('ALTER TABLE care_facilities DROP CONSTRAINT IF EXISTS care_facilities_claimed_by_user_id_foreign');
        }

        if (Schema::hasTable('facility_claims')) {
            Schema::table('facility_claims', function (Blueprint $table) {
                foreach (['care_facility_id', 'claimant_name', 'claimant_role', 'claimant_email', 'claimant_phone'] as $column) {
                    if (Schema::hasColumn('facility_claims', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('care_facilities')) {
            Schema::table('care_facilities', function (Blueprint $table) {
                foreach (['claimed_by_user_id', 'claimed_at'] as $column) {
                    if (Schema::hasColumn('care_facilities', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
