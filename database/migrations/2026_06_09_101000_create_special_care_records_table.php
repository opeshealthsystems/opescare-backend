<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_care_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('facility_id');
            $table->uuid('clinician_id');
            $table->string('care_type', 20);  // icu | nicu | dialysis | chemotherapy
            $table->date('record_date');
            $table->integer('session_number')->nullable();
            $table->jsonb('vitals')->nullable();
            $table->jsonb('medications')->nullable();
            $table->jsonb('observations')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->string('outcome', 100)->nullable();
            $table->string('status', 20)->default('recorded');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'facility_id']);
            $table->index(['care_type', 'record_date']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE special_care_records ADD CONSTRAINT chk_sc_type CHECK (care_type IN ('icu','nicu','dialysis','chemotherapy'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('special_care_records');
    }
};
