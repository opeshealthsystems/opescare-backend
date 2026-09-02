<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allied_health_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('facility_id');
            $table->uuid('therapist_id');
            $table->string('assessment_type', 30); // physiotherapy | occupational_therapy | speech_therapy | nutrition | social_work
            $table->date('assessment_date');
            $table->string('referral_reason', 500)->nullable();
            $table->text('subjective_findings')->nullable();
            $table->text('objective_findings')->nullable();
            $table->text('assessment_narrative')->nullable();
            $table->text('intervention_plan')->nullable();
            $table->string('goals', 2000)->nullable();
            $table->integer('sessions_recommended')->nullable();
            $table->string('follow_up_interval', 100)->nullable();
            $table->string('outcome_measure', 255)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'facility_id']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE allied_health_assessments ADD CONSTRAINT chk_aha_type CHECK (assessment_type IN ('physiotherapy','occupational_therapy','speech_therapy','nutrition','social_work'))");
            DB::statement("ALTER TABLE allied_health_assessments ADD CONSTRAINT chk_aha_status CHECK (status IN ('draft','finalized','amended'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('allied_health_assessments');
    }
};
