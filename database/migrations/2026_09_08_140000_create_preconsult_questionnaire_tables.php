<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The pre-consult questionnaire: a short clinical intake a patient completes
 * BEFORE an appointment.
 *
 * TWO TABLES, AND DELIBERATELY NOT THE SURVEY ONES. `patient_surveys` /
 * `survey_responses` are the SATISFACTION system ("how would you rate the wait
 * time?"): answers land in `numeric_response` / `text_response` with no
 * clinical semantics, no provenance and no consent scoping. Symptom reports
 * filed there would be indistinguishable from feedback, and nothing
 * downstream would treat them as clinical. This is its own storage.
 *
 * `preconsult_answers.provenance` is the point of the design. It is
 * `patient_reported` on every row and there is no other legal value — the same
 * structural line App\Enums\VitalProvenance draws between a home cuff and a
 * nurse's. Nothing in this feature writes to `triage_vital_signs`,
 * `vital_signs`, `diagnoses` or `allergy_records`.
 *
 * NOTHING HERE GATES CARE. `appointments` gains no column, no flag and no
 * foreign key pointing at a submission: an appointment cannot know whether its
 * questionnaire was answered, so no scheduling code can ever branch on it.
 * The relationship is one-way, from this table to the appointment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preconsult_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // One submission per appointment. The unique index is what makes
            // "start or resume" a single idempotent operation.
            $table->uuid('appointment_id')->unique();

            // Denormalised from the appointment at creation time so the
            // clinician read and the audit trail are patient- and
            // facility-scoped without a join. NEVER from user input — the
            // service copies both off the appointment row.
            $table->uuid('patient_id')->index();
            $table->uuid('facility_id')->index();

            // Which definition (config/preconsult.php) these answers were
            // given under. An answer is only interpretable against its own
            // version, so it is stamped, not looked up at read time.
            $table->string('definition_key');
            $table->unsignedInteger('definition_version');

            // draft | submitted  (App\Enums\PreconsultStatus)
            $table->string('status')->default('draft');

            // The user who actually filled it in — the patient themselves, or
            // a guardian acting through the FamilyLink act-as mechanism. Kept
            // separate from patient_id so the record says who spoke.
            $table->uuid('completed_by_user_id')->nullable()->index();

            $table->timestampTz('started_at');
            $table->timestampTz('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');

            $table->index(['patient_id', 'status']);
        });

        Schema::create('preconsult_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('submission_id');

            // The question's key in config/preconsult.php. Not its text: the
            // text lives in lang/{en,fr}/preconsult.php and is rendered at
            // read time in the reader's own language.
            $table->string('question_key');

            // The answer exactly as given. `value_text` for free text,
            // `value_choice` for a single choice — separate columns so a
            // choice stays a queryable token rather than prose.
            $table->text('value_text')->nullable();
            $table->string('value_choice')->nullable();

            // Always 'patient_reported'. Present on the ROW, not implied by
            // the table, so no reader can lose it by selecting columns.
            $table->string('provenance')->default('patient_reported');

            $table->timestamps();

            $table->foreign('submission_id')->references('id')->on('preconsult_submissions')->onDelete('cascade');
            $table->unique(['submission_id', 'question_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preconsult_answers');
        Schema::dropIfExists('preconsult_submissions');
    }
};
