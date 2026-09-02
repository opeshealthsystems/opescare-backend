<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('facility_id');
            $table->uuid('nurse_id');
            $table->string('record_type', 20);  // mar | progress | handover | admission_assessment | wound | incident | fall_risk | pressure_ulcer
            $table->date('record_date');
            $table->jsonb('content');   // flexible payload per type
            $table->string('ward', 100)->nullable();
            $table->string('bed_number', 20)->nullable();
            $table->string('shift', 20)->nullable();   // morning | afternoon | night
            $table->string('status', 20)->default('recorded');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'facility_id']);
            $table->index(['record_type', 'record_date']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE nursing_records ADD CONSTRAINT chk_nr_type CHECK (record_type IN ('mar','progress','handover','admission_assessment','wound','incident','fall_risk','pressure_ulcer'))");
            DB::statement("ALTER TABLE nursing_records ADD CONSTRAINT chk_nr_shift CHECK (shift IS NULL OR shift IN ('morning','afternoon','night'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_records');
    }
};
