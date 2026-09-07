<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The volunteer blood donor register (SB-02 "Find Donors", SB-03 "Become a
 * Donor").
 *
 * ── This table exists so that donor search is NOT patient search ───────────
 * The reference screen shows a browsable list of people with their names,
 * ages, blood groups, last-donation dates, distances and phone numbers. Read
 * straight off `patients`, that is enumeration of patients and of health data,
 * which this platform forbids outright — no `LIKE` search over patients, no
 * patient lists, exact Health ID only.
 *
 * A separate register changes what is being listed. Nobody appears here by
 * being a patient. A person appears only by filling in SB-03 and saying yes,
 * and every field below exists to keep that consent meaningful:
 *
 *   - `listing_consent_at` is NOT NULL for a row to be searchable at all.
 *     Consent is a timestamp rather than a boolean so it can be shown back
 *     ("you joined on…") and audited, and withdrawing sets `status`, never
 *     deletes the record of having consented.
 *
 *   - `share_phone` is a SECOND, separate consent. A donor may agree to be
 *     listed without agreeing to have their number handed out; the search
 *     result then offers a platform-brokered contact instead of a `tel:` link.
 *
 *   - `city` / `region` and nothing finer. The reference prints "1.2 km away",
 *     which is a live location for a named person — this stores the area a
 *     donor chose to publish, and the app sorts by that. A donor's coordinates
 *     are never written here.
 *
 *   - `status` — active | paused | withdrawn. Pausing is the common case (just
 *     donated, travelling, unwell) and must not cost somebody their place in
 *     the register.
 *
 * ── What is deliberately absent ────────────────────────────────────────────
 * No photograph, no date of birth, no weight, no medical history. SB-03 asks
 * eligibility questions to tell the donor whether they are likely to qualify;
 * the ANSWERS are not kept, because the blood bank assesses every donor on the
 * day and a stored "has a chronic condition" flag would be a health record
 * this platform has no reason to hold.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blood_donors')) {
            return;
        }

        Schema::create('blood_donors', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // One register entry per patient. A second attempt updates the
            // first rather than creating a duplicate person.
            $table->uuid('patient_id')->unique();

            $table->string('blood_group', 8);

            // Published area only — never coordinates.
            $table->string('city')->nullable();
            $table->string('region')->nullable();

            $table->string('status', 16)->default('active'); // active|paused|withdrawn

            /*
             * Consent, twice over. Listing is required for the row to appear at
             * all; sharing a phone number is its own decision.
             */
            $table->timestamp('listing_consent_at')->nullable();
            $table->boolean('share_phone')->default(false);
            $table->string('contact_phone', 32)->nullable();

            // Self-reported, and used only to show when someone was last able
            // to give — the blood bank makes the eligibility decision.
            $table->date('last_donated_on')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['blood_group', 'status']);
            $table->index(['city', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_donors');
    }
};
