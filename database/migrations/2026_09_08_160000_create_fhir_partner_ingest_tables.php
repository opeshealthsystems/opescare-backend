<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storage for clinical data PUSHED IN by an external partner over FHIR R4.
 *
 * -- Why these are separate tables ----------------------------------------
 * App\Enums\VitalProvenance already states the rule this migration obeys:
 * provenance in this platform is STRUCTURAL, not a label a writer chooses.
 * `triage_vital_signs`/`vital_signs` mean "a nurse measured this";
 * `lab_results` means "this laboratory released this number";
 * `patient_recorded_vitals` means "the patient typed this at home".
 *
 * A partner push is none of those. A hospital's own EMR, a lab middleware or
 * a partner clinic is asserting something over an API - nobody at THIS
 * facility observed it, and no reader may mistake it for something they did.
 * Writing partner claims into `lab_results` or `vital_signs` would erase that
 * difference permanently, because those tables have no column that could
 * carry it.
 *
 * So partner-pushed resources live here, are read as partner-asserted, and
 * carry `provenance = 'partner_reported'` pinned by a database default that
 * no write path may override.
 *
 * -- Immutability ---------------------------------------------------------
 * Rows are append-only. A partner correcting itself POSTs a replacement that
 * points at the row it replaces through `supersedes_id`; the superseded row
 * stays and its `status` becomes 'amended' or 'entered-in-error'. Nothing is
 * overwritten and nothing is deleted (the model guards enforce this). The one
 * sanctioned erasure remains the Postgres CASCADE from `patients` when a
 * retention period ends.
 *
 * `fhir_source` keeps the resource exactly as the partner posted it, so a
 * dispute about what was sent is answered from the record rather than from
 * this platform's interpretation of it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fhir_partner_diagnostic_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('facility_id');
            $table->string('integration_client_id', 191);

            $table->string('loinc_code', 32);
            $table->string('loinc_display', 255)->nullable();
            $table->string('category', 32)->nullable();
            $table->string('status', 32)->default('final');
            $table->text('conclusion')->nullable();

            $table->timestampTz('effective_at');
            $table->timestampTz('issued_at')->nullable();

            // Pinned by the database. Not fillable on the model - a write path
            // that could pass this would be a write path that could lie.
            $table->string('provenance', 32)->default('partner_reported');
            $table->string('source_system', 191);

            $table->uuid('supersedes_id')->nullable();
            $table->jsonb('fhir_source');
            $table->string('idempotency_key', 191)->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('facility_id')->references('id')->on('facilities');

            $table->index(['patient_id', 'effective_at']);
            $table->index(['facility_id', 'effective_at']);
        });

        Schema::create('fhir_partner_observations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('facility_id');
            $table->string('integration_client_id', 191);
            $table->uuid('diagnostic_report_id')->nullable();

            // 'vital-signs' or 'laboratory'. Decided from the posted
            // Observation.category, never guessed.
            $table->string('observation_kind', 32);

            $table->string('loinc_code', 32);
            $table->string('loinc_display', 255)->nullable();

            $table->decimal('value_quantity', 14, 4)->nullable();
            $table->string('value_unit', 64)->nullable();
            $table->string('value_string', 255)->nullable();
            // Multi-component observations (a blood pressure is two LOINC
            // components under one panel code) are kept whole here.
            $table->jsonb('components')->nullable();

            // The partner's OWN interpretation flag, stored as the partner's
            // claim. This platform's VitalBandResolver is not run over it and
            // its output is never presented as this platform's judgement.
            $table->string('partner_interpretation', 32)->nullable();
            $table->string('reference_range', 191)->nullable();

            $table->timestampTz('effective_at');
            $table->string('status', 32)->default('final');

            $table->string('provenance', 32)->default('partner_reported');
            $table->string('source_system', 191);

            $table->uuid('supersedes_id')->nullable();
            $table->jsonb('fhir_source');
            $table->string('idempotency_key', 191)->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('facility_id')->references('id')->on('facilities');
            $table->foreign('diagnostic_report_id')->references('id')->on('fhir_partner_diagnostic_reports')->cascadeOnDelete();

            $table->index(['patient_id', 'effective_at']);
            $table->index(['patient_id', 'loinc_code']);
            $table->index(['facility_id', 'effective_at']);
        });

        Schema::create('fhir_partner_immunizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('facility_id');
            $table->string('integration_client_id', 191);

            $table->string('cvx_code', 16);
            $table->string('vaccine_display', 255)->nullable();
            $table->string('status', 32)->default('completed');

            $table->timestampTz('occurrence_at');
            $table->string('lot_number', 64)->nullable();
            $table->string('manufacturer', 191)->nullable();
            $table->decimal('dose_quantity', 10, 3)->nullable();
            $table->string('dose_unit', 32)->nullable();
            $table->string('route_code', 32)->nullable();
            $table->string('site_code', 32)->nullable();

            $table->string('provenance', 32)->default('partner_reported');
            $table->string('source_system', 191);

            $table->uuid('supersedes_id')->nullable();
            $table->jsonb('fhir_source');
            $table->string('idempotency_key', 191)->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('facility_id')->references('id')->on('facilities');

            $table->index(['patient_id', 'occurrence_at']);
            $table->index(['facility_id', 'occurrence_at']);
        });

        /*
         * The self-referencing "this replaces that" links.
         *
         * Added after the CREATEs rather than inside them: Laravel emits a
         * table's foreign keys before its primary key on Postgres, so a
         * self-reference declared inline fails with "there is no unique
         * constraint matching given keys".
         */
        Schema::table('fhir_partner_diagnostic_reports', function (Blueprint $table) {
            $table->foreign('supersedes_id')->references('id')->on('fhir_partner_diagnostic_reports')->nullOnDelete();
        });

        Schema::table('fhir_partner_observations', function (Blueprint $table) {
            $table->foreign('supersedes_id')->references('id')->on('fhir_partner_observations')->nullOnDelete();
        });

        Schema::table('fhir_partner_immunizations', function (Blueprint $table) {
            $table->foreign('supersedes_id')->references('id')->on('fhir_partner_immunizations')->nullOnDelete();
        });

        /*
         * Every ingest attempt, accepted or refused, with the reason.
         *
         * The refusals are the point. "A partner tried to write against a
         * Health ID that does not resolve" and "a partner tried to write
         * without consent" are the two events this door exists to make
         * visible, and neither leaves any other trace: a refused write stores
         * no clinical row by definition.
         *
         * patient_id is nullable and is NOT a foreign key: an attempt against
         * an unresolvable Health ID has no patient to point at, which is
         * exactly the case worth recording. The submitted identifier is kept
         * hashed rather than in the clear so this table cannot itself become a
         * list of Health IDs to try.
         */
        Schema::create('fhir_ingest_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('integration_client_id', 191);
            $table->uuid('facility_id')->nullable();
            $table->uuid('patient_id')->nullable();

            $table->string('resource_type', 64);
            $table->string('outcome', 16);            // accepted | refused
            $table->string('refusal_code', 64)->nullable();
            $table->text('refusal_detail')->nullable();
            $table->unsignedSmallInteger('http_status');

            $table->string('subject_identifier_hash', 64)->nullable();
            $table->string('idempotency_key', 191)->nullable();
            $table->uuid('created_resource_id')->nullable();
            $table->timestampTz('created_at');

            $table->index(['integration_client_id', 'created_at']);
            $table->index(['outcome', 'refusal_code', 'created_at']);
            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fhir_ingest_events');
        Schema::dropIfExists('fhir_partner_immunizations');
        Schema::dropIfExists('fhir_partner_observations');
        Schema::dropIfExists('fhir_partner_diagnostic_reports');
    }
};
