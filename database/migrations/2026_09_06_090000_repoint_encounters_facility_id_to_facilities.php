<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Move `encounters.facility_id` from the directory register to the tenant one.
 *
 * There are two facility tables. `care_facilities` is the public directory
 * (2,728 rows, bulk-imported, browsable); `facilities` is the operational tenant
 * that actually has staff, appointments and prescriptions (482 rows). Only 468
 * directory rows are linked to a tenant.
 *
 * Every clinical table hangs off `facilities` — appointments, appointment_slots,
 * staff_profiles, prescriptions, consent_grants, lab_orders, provider tokens.
 * `encounters` is the ONE exception: its FK points at `care_facilities`.
 *
 * The concrete damage: a provider token carries a `facilities` id, and
 * `MobileDoctorChartController::relationshipBasis()` passes it into
 * `hasFacilityEpisode()`. The encounter branch of that check compares a
 * `facilities` id against a column holding `care_facilities` ids, so it can
 * never match. Basis 4 is degraded — the visit / appointment / prescription
 * branches still work, so it is not gone, and the failure is FAIL-CLOSED: a
 * dead comparison can only deny access, never grant it. The same broken branch
 * exists a second time in `MobileMessagingController::hasFacilityEpisode()`.
 *
 * Why now: the table holds a single row. Every encounter written from here on
 * makes this harder, and there is no code path that writes one yet (there is no
 * `Encounter::create()` anywhere — three readers, no writer), so the window is
 * open and cheap. It closes the moment a facility starts recording visits.
 *
 * The remap is exact, not fuzzy: each row's current value is a `care_facilities`
 * id, and that row carries the `facility_id` it belongs to. Nothing is matched
 * on a name. If ANY row cannot be resolved that way, this migration ABORTS
 * rather than nulling the column or guessing — an encounter pointed at the wrong
 * hospital is worse than a migration that refuses to run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('encounters') || ! Schema::hasColumn('encounters', 'facility_id')) {
            return;
        }

        // Refuse on anything we cannot resolve exactly.
        $unresolvable = DB::table('encounters as e')
            ->whereNotNull('e.facility_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('care_facilities as cf')
                    ->whereColumn('cf.id', 'e.facility_id')
                    ->whereNotNull('cf.facility_id');
            })
            // A row already pointing at a tenant is fine — this migration is
            // re-runnable in that sense.
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('facilities as f')
                    ->whereColumn('f.id', 'e.facility_id');
            })
            ->count();

        if ($unresolvable > 0) {
            throw new \RuntimeException(
                "Cannot repoint encounters.facility_id: {$unresolvable} row(s) hold a value that is "
                . 'neither a facilities id nor a care_facilities row linked to one. Link those '
                . 'directory listings to their tenant first — this migration will not guess which '
                . 'hospital an encounter belongs to.'
            );
        }

        DB::transaction(function () {
            // ORDER MATTERS. The constraint must come off BEFORE the rows move.
            // Updating first fails with SQLSTATE 23503: the new value is a
            // `facilities` id, and the old FK still demands a `care_facilities`
            // one, so every row is rejected by the very constraint being
            // replaced. (Found by running it — the transaction rolled back
            // cleanly, which is the only reason that was cheap.)
            $existing = DB::select("
                SELECT conname FROM pg_constraint
                WHERE conrelid = 'encounters'::regclass
                  AND contype = 'f'
                  AND conname = 'encounters_facility_id_foreign'
            ");

            if ($existing) {
                DB::statement('ALTER TABLE encounters DROP CONSTRAINT encounters_facility_id_foreign');
            }

            // Exact remap through the directory row's own link. No name matching.
            DB::statement(<<<'SQL'
                UPDATE encounters e
                SET facility_id = cf.facility_id
                FROM care_facilities cf
                WHERE cf.id = e.facility_id
                  AND cf.facility_id IS NOT NULL
            SQL);

            // Only now is every row a valid `facilities` id.
            DB::statement(<<<'SQL'
                ALTER TABLE encounters
                ADD CONSTRAINT encounters_facility_id_foreign
                FOREIGN KEY (facility_id) REFERENCES facilities (id)
            SQL);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('encounters') || ! Schema::hasColumn('encounters', 'facility_id')) {
            return;
        }

        // Reversing needs every row to map back to exactly ONE directory listing.
        // A tenant with two listings is ambiguous, and picking one would silently
        // move an encounter to a different address.
        $ambiguous = DB::select(<<<'SQL'
            SELECT e.facility_id, COUNT(cf.id) AS listings
            FROM encounters e
            JOIN care_facilities cf ON cf.facility_id = e.facility_id
            WHERE e.facility_id IS NOT NULL
            GROUP BY e.facility_id
            HAVING COUNT(cf.id) <> 1
        SQL);

        if ($ambiguous) {
            throw new \RuntimeException(
                'Cannot reverse: ' . count($ambiguous) . ' facility id(s) map to zero or several '
                . 'care_facilities listings, so the original value cannot be restored unambiguously.'
            );
        }

        DB::transaction(function () {
            // Same ordering rule as up(): drop, then move, then re-add.
            $existing = DB::select("
                SELECT conname FROM pg_constraint
                WHERE conrelid = 'encounters'::regclass
                  AND contype = 'f'
                  AND conname = 'encounters_facility_id_foreign'
            ");

            if ($existing) {
                DB::statement('ALTER TABLE encounters DROP CONSTRAINT encounters_facility_id_foreign');
            }

            DB::statement(<<<'SQL'
                UPDATE encounters e
                SET facility_id = cf.id
                FROM care_facilities cf
                WHERE cf.facility_id = e.facility_id
            SQL);

            DB::statement(<<<'SQL'
                ALTER TABLE encounters
                ADD CONSTRAINT encounters_facility_id_foreign
                FOREIGN KEY (facility_id) REFERENCES care_facilities (id)
            SQL);
        });
    }
};
