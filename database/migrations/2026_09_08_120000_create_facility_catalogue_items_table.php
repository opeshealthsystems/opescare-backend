<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What THIS facility actually offers, drawn from the institutional dictionary.
 *
 * `clinical_code_dictionary` says what exists in the world: every WHO ICD-10
 * category, every LOINC term, and whatever the imaging, procedure, department
 * and specialty importers add next. It says nothing whatever about who does
 * any of it. So the finder could show a hospital and could show a code, and
 * could not answer the only question a patient actually asks — "does this
 * place do that test?" — because nothing joined the two.
 *
 * This table is that join, and it is deliberately the FACILITY's own row: the
 * dictionary half is quoted, the rest is theirs.
 *
 * ── Why (system, code) and not the dictionary's uuid ─────────────────────
 * The dictionary is re-imported wholesale, per release, by importers that name
 * their source and version. A row's uuid is an artefact of the import that
 * happened to create it; `system` + `code` is the identity the PUBLISHER gave
 * it, is the dictionary's own unique key, and is what `code_system_mappings`
 * already speaks. Pointing at the uuid would mean a fresh ICD-10 release could
 * silently empty every hospital's service list — the codes still exist, but
 * the rows carrying them are new. A catalogue must survive its dictionary
 * being re-imported.
 *
 * There is deliberately NO foreign key onto the dictionary for the same
 * reason: a FK would either block a re-import that replaces rows, or cascade
 * a facility's curated list away with them. The reference is by published
 * value, resolved at read time, and a code the dictionary cannot resolve is
 * shown as unresolved rather than hidden — see FacilityCatalogueItem.
 *
 * ── Money ────────────────────────────────────────────────────────────────
 * XAF has exponent 0. There is no centime; 1500 FCFA is 1500, not 1500.00,
 * and a float here would eventually print 1499.9999. Whole francs, integer
 * column, nullable — a facility that has not published a price has NOT
 * published a price, and absent is not zero.
 *
 * NOTHING IS GATED ON THIS. A price on a row is information for a patient
 * deciding where to go. It is not a paywall, it does not gate care, and
 * CareIsNeverGatedOnPaymentTest holds that line platform-wide.
 *
 * ── Rows are retired, never deleted ──────────────────────────────────────
 * `is_active = false` is what "we stopped offering this" means. A hard delete
 * is wrong because the reference from everything downstream — an order, a
 * quote, a search result, an audit line — is by VALUE (facility + system +
 * code), not by a foreign key that a database could protect. Deleting the row
 * would not break anything loudly; it would quietly turn "this hospital
 * offered this test in March, at this price" into "it never did". The unique
 * key is on (facility_id, system, code) with no regard to is_active precisely
 * so that re-offering a service revives the original row and its history
 * rather than opening a second one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_catalogue_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // The tenant facility (`facilities`), not the public directory
            // (`care_facilities`). This is a row a signed-in staff member of
            // that facility wrote, so it belongs to the register that holds
            // accounts and postings.
            $table->uuid('facility_id');

            // The quoted half. Free-form `system` on purpose: ICD-10 and LOINC
            // are what is loaded today, imaging / procedures / departments /
            // specialties are being imported alongside this, and a hardcoded
            // list of two would have to be found and edited to admit the third.
            $table->string('system', 20);
            $table->string('code', 40);

            // The facility's own half.
            //
            // `local_name` is what THEY call it at the counter — "Test VIH" for
            // a LOINC term whose published display is nine words of English.
            // It never overwrites the published display; both are shown, so a
            // patient reads the local name and the wire still carries the code.
            $table->string('local_name', 200)->nullable();

            // Whole francs. See the money note above.
            $table->unsignedBigInteger('price_xaf')->nullable();

            // "Same day", "48h", "Tuesdays only". Free text because the honest
            // shape of this varies per service and a structured minutes column
            // would force every facility to invent a number it does not have.
            $table->string('turnaround', 120)->nullable();
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);

            // Who put it on the list, and who took it off. Not audit theatre:
            // a price is a public claim about a facility, and the person who
            // made it has to be nameable.
            $table->uuid('added_by')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->uuid('deactivated_by')->nullable();

            $table->timestamps();

            // One row per service per facility, active or retired.
            $table->unique(['facility_id', 'system', 'code']);
            // The catalogue screen's own query, and the finder's later one.
            $table->index(['facility_id', 'is_active']);
            $table->index(['system', 'code']);

            // A facility's service list has no meaning without the facility,
            // and it is not clinical history — no patient row points here.
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            // A staff member leaving must never delete the facility's list.
            $table->foreign('added_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deactivated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_catalogue_items');
    }
};
