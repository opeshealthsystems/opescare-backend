<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. MDR-TB cases
        Schema::create('mdr_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients');
            $table->foreignUuid('facility_id')->constrained('facilities');
            $table->date('registered_at');
            $table->string('diagnosis_basis', 50)->default('culture');
            $table->jsonb('drug_resistance_profile')->nullable();
            $table->string('treatment_regimen', 100)->nullable();
            $table->date('treatment_start_date')->nullable();
            $table->date('treatment_end_date')->nullable();
            $table->string('treatment_outcome', 30)->nullable();
            $table->foreignUuid('supervising_doctor_id')->nullable()->constrained('users');
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('facility_id');
            $table->index('status');
        });

        // 2. HIV counselling sessions
        Schema::create('hiv_counselling_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients');
            $table->foreignUuid('facility_id')->constrained('facilities');
            $table->foreignUuid('counsellor_id')->constrained('users');
            $table->string('session_type', 30);
            $table->date('session_date');
            $table->string('test_result', 20)->nullable();
            $table->integer('cd4_count')->nullable();
            $table->integer('viral_load')->nullable();
            $table->boolean('on_art')->default(false);
            $table->string('art_regimen', 100)->nullable();
            $table->jsonb('risk_factors')->nullable();
            $table->text('counselling_notes')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->boolean('consent_obtained')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('facility_id');
            $table->index('counsellor_id');
        });

        // 3. AEFI reports
        Schema::create('aefi_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients');
            $table->foreignUuid('facility_id')->constrained('facilities');
            $table->uuid('immunization_record_id')->nullable();
            $table->foreignUuid('reporter_id')->constrained('users');
            $table->date('report_date');
            $table->date('onset_date');
            $table->string('severity', 20);
            $table->text('event_description');
            $table->string('vaccine_name', 200);
            $table->string('vaccine_lot', 100)->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->string('causality_assessment', 30)->nullable();
            $table->string('outcome', 30)->nullable();
            $table->text('action_taken')->nullable();
            $table->boolean('reported_to_authorities')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('facility_id');
            $table->index('immunization_record_id');
        });

        // 4. Palliative care plans
        Schema::create('palliative_care_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients');
            $table->foreignUuid('facility_id')->constrained('facilities');
            $table->foreignUuid('lead_clinician_id')->constrained('users');
            $table->text('diagnosis');
            $table->text('prognosis')->nullable();
            $table->text('goals_of_care');
            $table->text('pain_management_plan')->nullable();
            $table->jsonb('symptom_management')->nullable();
            $table->text('psychological_support')->nullable();
            $table->text('spiritual_support')->nullable();
            $table->text('family_support')->nullable();
            $table->boolean('dnr_status')->default(false);
            $table->uuid('advance_directive_id')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('facility_id');
            $table->index('status');
        });

        // 5. Occupational health assessments
        Schema::create('occupational_health_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients');
            $table->foreignUuid('facility_id')->constrained('facilities');
            $table->foreignUuid('examiner_id')->constrained('users');
            $table->date('assessment_date');
            $table->string('assessment_type', 30);
            $table->string('job_title', 150)->nullable();
            $table->string('employer', 200)->nullable();
            $table->jsonb('exposure_history')->nullable();
            $table->text('clinical_findings')->nullable();
            $table->string('fitness_conclusion', 30);
            $table->text('restrictions')->nullable();
            $table->date('next_review_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('facility_id');
            $table->index('assessment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occupational_health_assessments');
        Schema::dropIfExists('palliative_care_plans');
        Schema::dropIfExists('aefi_reports');
        Schema::dropIfExists('hiv_counselling_sessions');
        Schema::dropIfExists('mdr_cases');
    }
};
