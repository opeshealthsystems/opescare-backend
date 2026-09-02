<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ward_admin_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('facility_id');
            $table->uuid('actor_id');
            $table->string('record_type', 30);
            // lama | transfer | investigation_request | patient_complaint | procedure_consent
            // pharmacy_dispensing | medication_reconciliation | blood_transfusion
            // blood_bank_request | glucose_log | arv_card | fitness_certificate
            // orthopaedic_chart | resuscitation | mental_health_involuntary
            $table->date('record_date');
            $table->jsonb('content');
            $table->string('status', 20)->default('recorded');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'facility_id']);
            $table->index(['record_type', 'record_date']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE ward_admin_records ADD CONSTRAINT chk_war_type CHECK (record_type IN ('lama','transfer','investigation_request','patient_complaint','procedure_consent','pharmacy_dispensing','medication_reconciliation','blood_transfusion','blood_bank_request','glucose_log','arv_card','fitness_certificate','orthopaedic_chart','resuscitation','mental_health_involuntary'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ward_admin_records');
    }
};
