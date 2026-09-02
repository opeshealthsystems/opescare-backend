<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('psychiatric_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('facility_id');
            $table->uuid('clinician_id');
            $table->date('assessment_date');
            $table->string('referral_source', 100)->nullable();
            $table->jsonb('presenting_complaints')->nullable();
            $table->string('psychiatric_history', 2000)->nullable();
            $table->string('family_history', 2000)->nullable();
            $table->string('substance_use_history', 1000)->nullable();
            $table->string('mental_state_examination', 5000)->nullable();
            $table->jsonb('risk_factors')->nullable();
            $table->string('diagnosis_icd', 30)->nullable();
            $table->string('diagnosis_narrative', 1000)->nullable();
            $table->string('management_plan', 3000)->nullable();
            $table->jsonb('medications_current')->nullable();
            $table->string('risk_level', 20)->nullable();
            $table->string('follow_up_plan', 500)->nullable();
            $table->string('notes', 5000)->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('facility_id');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE psychiatric_assessments ADD CONSTRAINT chk_psy_risk_level CHECK (risk_level IN ('low','medium','high','very_high'))");
            DB::statement("ALTER TABLE psychiatric_assessments ADD CONSTRAINT chk_psy_status CHECK (status IN ('draft','finalized','amended'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('psychiatric_assessments');
    }
};
