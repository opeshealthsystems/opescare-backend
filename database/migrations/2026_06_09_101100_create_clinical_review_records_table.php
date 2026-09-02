<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_review_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id');
            $table->uuid('reviewer_id');
            $table->uuid('patient_id')->nullable();
            $table->string('review_type', 30);
            // maternal_death_review | perinatal_mortality_review | coroners_notification
            // verbal_autopsy | adverse_event | medicolegal | notifiable_disease | malaria_case
            $table->date('review_date');
            $table->string('case_reference', 100)->nullable();
            $table->text('summary');
            $table->jsonb('findings')->nullable();
            $table->jsonb('recommendations')->nullable();
            $table->string('outcome', 255)->nullable();
            $table->string('reported_to_authority', 255)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['facility_id', 'review_type']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE clinical_review_records ADD CONSTRAINT chk_crr_type CHECK (review_type IN ('maternal_death_review','perinatal_mortality_review','coroners_notification','verbal_autopsy','adverse_event','medicolegal','notifiable_disease','malaria_case'))");
            DB::statement("ALTER TABLE clinical_review_records ADD CONSTRAINT chk_crr_status CHECK (status IN ('draft','submitted','finalised'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_review_records');
    }
};
