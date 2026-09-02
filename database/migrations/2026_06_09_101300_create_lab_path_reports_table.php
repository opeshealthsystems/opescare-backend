<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_path_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('facility_id');
            $table->uuid('reported_by');
            $table->string('report_type', 20);   // lab | pathology | autopsy_pathology
            $table->date('collected_date')->nullable();
            $table->date('reported_date');
            $table->string('specimen_type', 100)->nullable();
            $table->string('test_name', 255);
            $table->text('results');
            $table->string('reference_range', 500)->nullable();
            $table->string('interpretation', 1000)->nullable();
            $table->boolean('critical_value')->default(false);
            $table->string('status', 20)->default('preliminary');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'facility_id']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE lab_path_reports ADD CONSTRAINT chk_lpr_type CHECK (report_type IN ('lab','pathology','autopsy_pathology'))");
            DB::statement("ALTER TABLE lab_path_reports ADD CONSTRAINT chk_lpr_status CHECK (status IN ('preliminary','final','amended','cancelled'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_path_reports');
    }
};
