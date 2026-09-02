<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialty_diagnostic_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('facility_id');
            $table->uuid('reporting_clinician_id');
            $table->string('report_type', 20);   // echo | ecg | endoscopy
            $table->date('study_date');
            $table->string('indication', 500)->nullable();
            $table->text('findings');
            $table->string('impression', 2000)->nullable();
            $table->string('recommendation', 1000)->nullable();
            $table->jsonb('measurements')->nullable();
            $table->string('image_refs', 1000)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'facility_id']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE specialty_diagnostic_reports ADD CONSTRAINT chk_sdr_type CHECK (report_type IN ('echo','ecg','endoscopy'))");
            DB::statement("ALTER TABLE specialty_diagnostic_reports ADD CONSTRAINT chk_sdr_status CHECK (status IN ('draft','finalized','amended'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('specialty_diagnostic_reports');
    }
};
