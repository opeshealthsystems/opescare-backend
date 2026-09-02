<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perioperative_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('facility_id');
            $table->uuid('provider_id');
            $table->string('record_type', 30);         // anaesthesia | ssc | postop_recovery
            $table->string('procedure_name', 255)->nullable();
            $table->string('procedure_code', 30)->nullable();
            $table->dateTime('procedure_datetime')->nullable();
            $table->jsonb('checklist_data')->nullable();  // SSC items
            $table->string('anaesthesia_type', 50)->nullable();
            $table->string('anaesthesiologist_id')->nullable();
            $table->text('intraoperative_notes')->nullable();
            $table->text('postop_notes')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->string('asa_grade', 5)->nullable();
            $table->boolean('complications')->default(false);
            $table->text('complications_detail')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'facility_id']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE perioperative_records ADD CONSTRAINT chk_peri_type CHECK (record_type IN ('anaesthesia','ssc','postop_recovery'))");
            DB::statement("ALTER TABLE perioperative_records ADD CONSTRAINT chk_peri_status CHECK (status IN ('draft','signed','amended'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('perioperative_records');
    }
};
