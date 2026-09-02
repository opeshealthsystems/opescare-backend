<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Provenance for directory rows that came from an outside dataset, and the
 * queue for the ones a machine must not decide alone.
 *
 * WHY
 * ---
 * `care_facilities` holds 903 rows lifted from the MINSANTE national registry.
 * 503 of them have no coordinates, so they can never surface in a "near me"
 * search; none has an email; 692 carry the literal string 'N/A' in
 * `phone_primary`. OpenStreetMap can fill a lot of that in — it is ODbL
 * licensed, which explicitly permits bulk extraction and redistribution
 * *provided the result is attributed*. (Google Places cannot: its terms forbid
 * storing Place data or building a competing directory.)
 *
 * That licence obligation is the reason these columns exist. A row enriched
 * from OSM must stay identifiable as OSM-derived for as long as it lives in
 * this table — including in any dump, export or replica taken from it — so the
 * attribution string is stored on the row rather than being left to a footer in
 * a Blade template that a future refactor could quietly drop.
 *
 * Column names are deliberately source-agnostic (`source_system`, not
 * `osm_...`): the next importer — a MINSANTE CSV refresh, a partner feed —
 * gets the same three columns instead of three more.
 *
 *   source_system      where the ROW originated. Only ever set on rows an
 *                      importer CREATED. An existing MINSANTE row that OSM
 *                      merely enriched keeps its own (NULL) origin — enrichment
 *                      is not authorship, and relabelling institutional data as
 *                      OSM-sourced would be a lie about its provenance.
 *   source_ref         the upstream element, namespaced: 'osm:node/967892138'.
 *                      Set on EVERY row an importer touched, created or not.
 *                      This is the attribution key.
 *   source_attribution the licence notice itself, carried by the data.
 *   source_synced_at   when the upstream record was last reconciled.
 *
 * The partial UNIQUE index on `source_ref` is load-bearing, not decoration: it
 * is the hard floor under the importer's idempotency. Two runs, a crash
 * mid-batch, or two OSM elements resolving to the same facility cannot produce
 * two rows for one upstream element — the database refuses.
 *
 * `facility_import_reviews` is where an importer puts what it will not decide.
 * A duplicated hospital in a directory people use to find care is worse than a
 * missing one, so an uncertain candidate is neither merged nor inserted: it is
 * parked here, with the score and distance that made it uncertain, for a human.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('care_facilities', function (Blueprint $table) {
            $table->string('source_system', 50)->nullable()->after('facility_code');
            $table->string('source_ref', 128)->nullable()->after('source_system');
            $table->string('source_attribution', 255)->nullable()->after('source_ref');
            $table->timestamp('source_synced_at')->nullable()->after('source_attribution');
        });

        // Partial UNIQUE: one care_facilities row per upstream element, while
        // leaving the 903 rows with no provenance (NULL) entirely alone.
        DB::statement(
            'CREATE UNIQUE INDEX care_facilities_source_ref_unique
             ON care_facilities (source_ref) WHERE source_ref IS NOT NULL'
        );

        DB::statement(
            'CREATE INDEX care_facilities_source_system_index
             ON care_facilities (source_system) WHERE source_system IS NOT NULL'
        );

        Schema::create('facility_import_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Which upstream record this is about.
            $table->string('source_system', 50);
            $table->string('source_ref', 128);
            $table->string('source_attribution', 255)->nullable();

            // Why a human is being asked.
            $table->string('reason', 64);              // see OsmFacilityImporter::REASON_*
            $table->string('status', 32)->default('pending'); // pending|imported|rejected|merged

            // The candidate, as the importer understood it. `candidate_name` is
            // nullable on purpose: 227 of OSM's 2,083 Cameroonian health
            // features carry no `name` tag at all, and those are exactly the
            // ones a machine must not act on. The reviewer works from `payload`
            // instead — the name is often sitting in a mistagged key there
            // ('dispensing=God Bless Phamacy'), which a person can read and a
            // heuristic should not guess at.
            $table->string('candidate_name')->nullable();
            $table->string('candidate_type', 64)->nullable();
            $table->string('candidate_city')->nullable();
            $table->string('candidate_region')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->jsonb('payload')->nullable();      // raw upstream tags, verbatim

            // The existing row it looked like, and how much.
            $table->uuid('matched_facility_id')->nullable();
            $table->string('matched_facility_name')->nullable();
            $table->decimal('match_score', 4, 3)->nullable();   // 0.000 – 1.000
            $table->integer('match_distance_m')->nullable();

            // The human's decision.
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();

            $table->unique(['source_system', 'source_ref']);
            $table->index(['status', 'reason']);

            $table->foreign('matched_facility_id')
                  ->references('id')->on('care_facilities')->nullOnDelete();
            $table->foreign('reviewed_by')
                  ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_import_reviews');

        DB::statement('DROP INDEX IF EXISTS care_facilities_source_system_index');
        DB::statement('DROP INDEX IF EXISTS care_facilities_source_ref_unique');

        Schema::table('care_facilities', function (Blueprint $table) {
            $table->dropColumn([
                'source_system',
                'source_ref',
                'source_attribution',
                'source_synced_at',
            ]);
        });
    }
};
