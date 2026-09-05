<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Patient-recorded vitals — a reading the PATIENT took, on their own device,
 * and typed in themselves.
 *
 * ── Why this is a separate table, and not a row in `triage_vital_signs` ──────
 *
 * A patient-recorded vital is NOT a clinical measurement. It was taken with an
 * uncalibrated home cuff or a supermarket scale, without a clinician present,
 * in circumstances nobody verified. It is still worth having — a fortnight of
 * home blood pressures is real evidence — but it must never be readable as if
 * a nurse had taken it.
 *
 * The platform's existing clinical readers (App\Http\Controllers\Api\Mobile\
 * MobileVitalsController, the triage module, every FHIR Observation export)
 * select from `triage_vital_signs`, `vital_signs` and `lab_results`. If a
 * self-reported glucose were inserted into any of those, every one of those
 * readers would surface it as a measured value, today, with no code change and
 * no warning. Provenance carried only in a column is provenance one forgotten
 * `where` clause away from being lost.
 *
 * So the separation is STRUCTURAL: self-reported readings live in their own
 * table that no clinical reader queries, and the `provenance` column carries a
 * database CHECK constraint pinning it to 'self_reported'. There is no INSERT
 * into this table that can claim to be a facility measurement, and no clinical
 * query that can accidentally pick one up. This mirrors how `blood_availability`
 * gates every stock read behind a `source_system` allow-list rather than
 * trusting the caller to filter.
 *
 * ── Immutability ────────────────────────────────────────────────────────────
 *
 * A recorded reading is an event: it is never edited in place. A patient who
 * mistypes voids the entry (`voided_at` + `void_reason`) and records a new one,
 * so the history shows both what was said and that it was retracted — the same
 * amend/void discipline the clinical modules follow.
 *
 * ── What is deliberately NOT here ───────────────────────────────────────────
 *
 * No `status` / `flag` / interpretation column. Classifying a self-reported
 * glucose as "high" would be this table's opinion presented as a finding.
 * The API returns an advisory band computed at read time and labelled as
 * advisory; the stored record is the number the patient entered and nothing
 * more.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_recorded_vitals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');

            // App\Enums\SelfReportedVitalMeasure — blood_pressure, weight,
            // temperature, blood_sugar. The four a patient can genuinely take
            // at home; nothing requiring a clinician is offered.
            $table->string('measure', 32);

            // One measure, up to two numbers. `value_secondary` is the
            // diastolic and is used by blood_pressure alone.
            $table->decimal('value_primary', 8, 2);
            $table->decimal('value_secondary', 8, 2)->nullable();

            // The unit the patient entered in, stored as entered. Nothing is
            // silently converted: a glucose recorded in mmol/L is returned in
            // mmol/L, because the conversion factor differs by analyte and a
            // wrong one is a dosing error.
            $table->string('unit', 16);

            // Fasting / post-meal / random, for glucose. MobileVitalsController
            // documents that it must return `unknown` for a lab glucose because
            // the fasting state "is not recorded anywhere in the schema". Here
            // it is: the patient is the only person who knows, and they are
            // being asked directly.
            $table->string('measurement_context', 32)->nullable();

            // The patient's own description of what they measured with. Free
            // text, never validated into a device registry it cannot back up.
            $table->string('device_label')->nullable();
            $table->text('note')->nullable();

            // When the reading was TAKEN, which is not when it was typed in.
            $table->timestampTz('measured_at');

            // ── Provenance ──────────────────────────────────────────────────
            // Pinned by a CHECK constraint below. This table cannot hold a row
            // that claims to be anything other than self-reported.
            $table->string('provenance', 32)->default('self_reported');
            $table->string('source_system', 64)->default('patient_mobile_app');

            // ── Retraction, never overwrite ─────────────────────────────────
            $table->timestampTz('voided_at')->nullable();
            $table->string('void_reason')->nullable();

            // The user account behind the patient token, for the audit trail.
            // Nullable: a patient record can exist without a linked user.
            $table->uuid('entered_by_user_id')->nullable();

            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('entered_by_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['patient_id', 'measure', 'measured_at']);
            $table->index(['patient_id', 'voided_at']);
        });

        // Provenance is not a hint. A row in this table is self-reported or it
        // does not exist.
        DB::statement(
            "ALTER TABLE patient_recorded_vitals
             ADD CONSTRAINT patient_recorded_vitals_provenance_check
             CHECK (provenance = 'self_reported')"
        );

        // A last line of defence under the API's range validation. These are
        // not clinical ranges — they are "no human ever measured this" bounds,
        // so that a bug in a future caller cannot persist a 900/600.
        DB::statement(
            "ALTER TABLE patient_recorded_vitals
             ADD CONSTRAINT patient_recorded_vitals_value_check
             CHECK (value_primary > 0 AND (value_secondary IS NULL OR value_secondary > 0))"
        );

        // A blood pressure is two numbers and the diastolic is the lower one.
        // A lone systolic is not a blood pressure and is not stored as one.
        DB::statement(
            "ALTER TABLE patient_recorded_vitals
             ADD CONSTRAINT patient_recorded_vitals_bp_check
             CHECK (
                 measure <> 'blood_pressure'
                 OR (value_secondary IS NOT NULL AND value_secondary < value_primary)
             )"
        );

        // Every other measure is a single number; a stray second value would
        // silently render as a fraction the patient never entered.
        DB::statement(
            "ALTER TABLE patient_recorded_vitals
             ADD CONSTRAINT patient_recorded_vitals_single_value_check
             CHECK (measure = 'blood_pressure' OR value_secondary IS NULL)"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_recorded_vitals');
    }
};
