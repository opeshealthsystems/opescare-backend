<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pediatric_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('facility_id');
            $table->uuid('clinician_id');
            $table->string('record_type', 30); // newborn_assessment | child_health_card | growth_chart | stillbirth_certificate
            $table->date('record_date');
            $table->integer('age_days')->nullable();
            $table->decimal('weight_kg', 5, 3)->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('head_circumference_cm', 5, 1)->nullable();
            $table->integer('apgar_1min')->nullable();
            $table->integer('apgar_5min')->nullable();
            $table->string('gestational_age_weeks', 10)->nullable();
            $table->jsonb('milestones')->nullable();
            $table->jsonb('immunisations_given')->nullable();
            $table->jsonb('growth_data')->nullable();       // array of {date, weight, height, hc}
            $table->text('clinical_notes')->nullable();
            $table->string('status', 20)->default('recorded');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'facility_id']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE pediatric_records ADD CONSTRAINT chk_ped_type CHECK (record_type IN ('newborn_assessment','child_health_card','growth_chart','stillbirth_certificate'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pediatric_records');
    }
};
