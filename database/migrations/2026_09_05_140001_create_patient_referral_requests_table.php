<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A patient's REQUEST for a referral. Not a referral.
 *
 * ── Why this is not a `referral_cases` row ──────────────────────────────────
 *
 * A referral is a clinical act: one clinician hands a patient's care to
 * another, with a clinical summary, a consent grant and a record package
 * attached. `referral_cases` models exactly that, and its own schema says so —
 * `referring_facility_id` is NOT NULL, because a referral without a referring
 * facility is not a referral.
 *
 * A patient asking to be referred is a different thing entirely, and if it were
 * stored as a `draft` referral_case then every reader of that table — the
 * facility referral queue, the patient's own referrals list, any export —
 * would show a case the patient invented as one a clinician issued. Status
 * columns are not a safe boundary here: `draft` reads as "nearly sent".
 *
 * So the request lives in its own table. It becomes a referral only when a
 * clinician creates a real `referral_cases` row and links it back through
 * `referral_case_id`, which a CHECK constraint permits only on an accepted
 * request. Until then there is nothing in `referral_cases` to be mistaken for
 * an issued referral, because there is nothing in `referral_cases` at all.
 *
 * ── Why `reported_urgency` is not `urgency` ─────────────────────────────────
 *
 * `referral_cases.urgency` is clinical triage — routine / urgent / emergency,
 * set by a clinician. This column is how urgent the patient FEELS it is. The
 * two must never be read as the same field, so it is named differently, its
 * values are named differently (routine / soon / urgent), and the API labels
 * it as the patient's own description.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_referral_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Shown to the patient and quotable at a desk, like the blood and
            // medicine request references: OC-RR-XXXXXXXX.
            $table->string('reference')->unique();

            $table->uuid('patient_id');

            // What the patient is asking for. Free text against a suggested
            // list — a directory of specialties the platform cannot guarantee
            // is complete must not become a hard allow-list that blocks a real
            // need.
            $table->string('requested_specialty')->nullable();

            // Where they would like to go, if they have a preference. Points at
            // `care_facilities` (the public directory carrying coordinates),
            // not `facilities` (the internal tenant record) — same choice, and
            // same reasoning, as blood_requests.care_facility_id.
            $table->uuid('preferred_care_facility_id')->nullable();

            $table->text('reason');
            $table->string('symptom_duration', 32)->nullable();

            // The PATIENT's sense of urgency. Never clinical triage. See above.
            $table->string('reported_urgency', 16)->default('routine');

            // App\Enums\PatientReferralRequestStatus.
            $table->string('status', 24)->default('submitted');

            $table->text('patient_note')->nullable();
            $table->string('contact_phone', 32)->nullable();

            // ── The clinician's decision — the only route to a referral ─────
            $table->timestampTz('reviewed_at')->nullable();
            $table->uuid('reviewed_by_id')->nullable();
            $table->text('review_note')->nullable();
            $table->text('decline_reason')->nullable();

            // Set ONLY when a clinician has issued the actual referral. Until
            // then the patient's list has a request with no referral behind it,
            // and says so.
            $table->uuid('referral_case_id')->nullable();

            // The patient can take their own request back; they cannot delete
            // it, because a facility may already have read it.
            $table->timestampTz('withdrawn_at')->nullable();
            $table->string('withdrawal_reason')->nullable();

            $table->string('source_system', 64)->default('patient_mobile_app');

            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('preferred_care_facility_id')->references('id')->on('care_facilities')->nullOnDelete();
            $table->foreign('reviewed_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('referral_case_id')->references('id')->on('referral_cases')->nullOnDelete();

            $table->index(['patient_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // A request cannot point at a referral unless a clinician accepted it.
        // Without this, a bug could attach a case id to a `submitted` row and
        // the patient would be shown a referral nobody issued.
        DB::statement(
            "ALTER TABLE patient_referral_requests
             ADD CONSTRAINT patient_referral_requests_case_link_check
             CHECK (referral_case_id IS NULL OR status = 'accepted')"
        );

        // Statuses are a closed set; anything outside it would render as an
        // unlabelled badge in two languages.
        DB::statement(
            "ALTER TABLE patient_referral_requests
             ADD CONSTRAINT patient_referral_requests_status_check
             CHECK (status IN ('submitted','under_review','accepted','declined','withdrawn'))"
        );

        DB::statement(
            "ALTER TABLE patient_referral_requests
             ADD CONSTRAINT patient_referral_requests_reported_urgency_check
             CHECK (reported_urgency IN ('routine','soon','urgent'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_referral_requests');
    }
};
