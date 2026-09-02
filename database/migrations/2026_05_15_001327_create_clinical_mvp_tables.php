<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('facility_id')->index();
            $table->uuid('provider_id')->nullable()->index();
            $table->string('visit_type'); // outpatient, emergency, lab-only, pharmacy-only
            $table->string('status')->default('open'); // open, in_progress, closed, cancelled
            $table->timestampTz('started_at')->useCurrent();
            $table->timestampTz('ended_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('triage_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('visit_id')->index();
            $table->uuid('nurse_id')->nullable()->index();
            $table->text('presenting_complaint')->nullable();
            $table->integer('pain_score')->nullable();
            $table->string('pregnancy_status')->nullable();
            $table->string('acuity_score')->nullable();
            $table->timestamps();

            $table->foreign('visit_id')->references('id')->on('visits')->onDelete('cascade');
            $table->foreign('nurse_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('vital_signs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('triage_record_id')->index();
            $table->decimal('temperature', 4, 1)->nullable()->comment('Celsius');
            $table->integer('blood_pressure_systolic')->nullable();
            $table->integer('blood_pressure_diastolic')->nullable();
            $table->integer('pulse')->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->decimal('oxygen_saturation', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable()->comment('kg');
            $table->decimal('height', 5, 2)->nullable()->comment('cm');
            $table->timestamps();

            $table->foreign('triage_record_id')->references('id')->on('triage_records')->onDelete('cascade');
        });

        Schema::create('clinical_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('visit_id')->index();
            $table->uuid('provider_id')->index();
            $table->text('history_of_present_illness')->nullable();
            $table->text('examination_findings')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->string('status')->default('draft'); // draft, signed, amended
            $table->uuid('amends_note_id')->nullable()->index();
            $table->timestampTz('signed_at')->nullable();
            $table->timestamps();

            $table->foreign('visit_id')->references('id')->on('visits')->onDelete('cascade');
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('diagnoses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('visit_id')->index();
            $table->uuid('provider_id')->index();
            $table->string('code_system')->nullable(); // e.g., ICD-10
            $table->string('code')->nullable();
            $table->string('display_name');
            $table->string('status')->default('active'); // active, resolved, entered-in-error
            $table->boolean('is_primary')->default(true);
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('visit_id')->references('id')->on('visits')->onDelete('cascade');
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('allergy_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('provider_id')->index();
            $table->string('substance');
            $table->string('severity')->default('moderate'); // low, moderate, high
            $table->string('status')->default('active'); // active, inactive, entered-in-error
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('clinical_notes', function (Blueprint $table) {
            $table->foreign('amends_note_id')->references('id')->on('clinical_notes')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('clinical_notes', function (Blueprint $table) {
            $table->dropForeign(['amends_note_id']);
        });

        Schema::dropIfExists('allergy_records');
        Schema::dropIfExists('diagnoses');
        Schema::dropIfExists('clinical_notes');
        Schema::dropIfExists('vital_signs');
        Schema::dropIfExists('triage_records');
        Schema::dropIfExists('visits');
    }
};
