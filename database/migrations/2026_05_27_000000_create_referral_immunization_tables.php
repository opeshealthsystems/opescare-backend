<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Referral Network
        Schema::create('referral_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('referring_facility_id')->index();
            $table->uuid('referring_provider_id')->nullable()->index();
            $table->uuid('receiving_facility_id')->nullable()->index();
            $table->string('receiving_specialty')->nullable();
            $table->string('receiving_provider_name')->nullable();
            $table->string('urgency')->default('routine'); // routine, urgent, emergency
            $table->string('status')->default('draft'); // draft, sent, accepted, rejected, cancelled, completed, expired
            $table->text('reason');
            $table->text('clinical_summary')->nullable();
            $table->json('included_record_types')->nullable(); // which record types are included in the package
            $table->uuid('consent_grant_id')->nullable()->index();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('accepted_at')->nullable();
            $table->uuid('accepted_by_id')->nullable()->index();
            $table->timestampTz('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('feedback')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->uuid('created_by_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('referring_facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('receiving_facility_id')->references('id')->on('facilities')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('referral_access_grants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('referral_case_id')->index();
            $table->uuid('patient_id')->index();
            $table->uuid('granted_to_facility_id')->index();
            $table->uuid('granted_by_id')->nullable()->index();
            $table->string('token', 128)->unique();
            $table->json('allowed_scopes'); // what data the receiving facility can access
            $table->string('status')->default('active'); // active, revoked, expired
            $table->timestampTz('expires_at');
            $table->timestampTz('first_accessed_at')->nullable();
            $table->timestampTz('last_accessed_at')->nullable();
            $table->unsignedInteger('access_count')->default(0);
            $table->timestampTz('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();

            $table->foreign('referral_case_id')->references('id')->on('referral_cases')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('granted_to_facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('granted_by_id')->references('id')->on('users')->onDelete('set null');
        });

        // Immunization Module
        Schema::create('immunization_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('facility_id')->index();
            $table->uuid('administered_by_id')->nullable()->index();
            $table->uuid('encounter_id')->nullable()->index();
            $table->string('vaccine_code'); // e.g. BCG, OPV, DPT, HepB
            $table->string('vaccine_system')->default('WHO-EPI'); // coding system
            $table->string('vaccine_name');
            $table->string('lot_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->dateTimeTz('administered_at');
            $table->integer('dose_number')->nullable(); // 1, 2, 3...
            $table->string('dose_sequence')->nullable(); // e.g. "primary_1", "booster_1"
            $table->string('route')->nullable(); // IM, SC, oral, intradermal
            $table->string('site')->nullable(); // left arm, right thigh, etc.
            $table->decimal('dose_quantity', 6, 2)->nullable();
            $table->string('dose_unit')->nullable(); // mL, mg
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('completed'); // completed, not_done, entered_in_error
            $table->text('not_done_reason')->nullable();
            $table->string('verification_status')->default('unverified'); // unverified, verified
            $table->boolean('is_historical')->default(false); // imported/self-reported
            $table->uuid('source_document_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('administered_by_id')->references('id')->on('users')->onDelete('set null');

            // Duplicate suppression: one vaccine+dose per patient per date per lot (within same facility)
            $table->index(['patient_id', 'vaccine_code', 'administered_at', 'lot_number'], 'immunization_dedupe_idx');
        });

        Schema::create('vaccination_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->string('vaccine_code');
            $table->string('vaccine_name');
            $table->integer('dose_number');
            $table->string('dose_sequence')->nullable();
            $table->date('due_date')->nullable();
            $table->date('earliest_date')->nullable();
            $table->date('latest_date')->nullable();
            $table->string('status')->default('due'); // due, overdue, completed, skipped, deferred
            $table->uuid('completed_by_immunization_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
        });

        Schema::create('adverse_reaction_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('immunization_record_id')->index();
            $table->uuid('patient_id')->index();
            $table->uuid('reported_by_id')->nullable()->index();
            $table->string('severity'); // mild, moderate, severe, life_threatening
            $table->text('description');
            $table->string('onset_timing')->nullable(); // immediate, within_1h, within_24h, delayed
            $table->timestampTz('onset_at')->nullable();
            $table->string('action_taken')->nullable();
            $table->string('outcome')->nullable(); // resolved, recovering, not_resolved, fatal, unknown
            $table->boolean('reported_to_authority')->default(false);
            $table->string('authority_report_reference')->nullable();
            $table->timestamps();

            $table->foreign('immunization_record_id')->references('id')->on('immunization_records')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('reported_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adverse_reaction_notes');
        Schema::dropIfExists('vaccination_schedules');
        Schema::dropIfExists('immunization_records');
        Schema::dropIfExists('referral_access_grants');
        Schema::dropIfExists('referral_cases');
    }
};
