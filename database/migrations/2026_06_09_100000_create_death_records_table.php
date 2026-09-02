<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('death_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignUuid('certifying_doctor_id')->constrained('users');
            $table->timestamp('deceased_at');
            $table->string('place_of_death', 50)->default('hospital');
            $table->string('manner_of_death', 30)->default('natural');
            $table->text('primary_cause');
            $table->jsonb('secondary_causes')->nullable();
            $table->string('duration_primary', 50)->nullable();
            $table->text('contributing_conditions')->nullable();
            $table->boolean('was_autopsy_performed')->default(false);
            $table->uuid('autopsy_report_id')->nullable();
            $table->foreignUuid('registrar_id')->nullable()->constrained('users');
            $table->timestamp('registered_at')->nullable();
            $table->uuid('official_document_id')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('facility_id');
            $table->index('certifying_doctor_id');
            $table->index('status');
            $table->index('deceased_at');
            $table->index('autopsy_report_id');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE death_records ADD CONSTRAINT chk_death_place CHECK (place_of_death IN ('hospital','home','other','unknown'))");
            DB::statement("ALTER TABLE death_records ADD CONSTRAINT chk_death_manner CHECK (manner_of_death IN ('natural','accident','homicide','suicide','undetermined','pending_investigation'))");
            DB::statement("ALTER TABLE death_records ADD CONSTRAINT chk_death_status CHECK (status IN ('draft','certified','registered','cancelled'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('death_records');
    }
};
