<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antenatal_visits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pregnancy_record_id')->constrained('pregnancy_records')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignUuid('provider_id')->constrained('users')->cascadeOnDelete();
            $table->date('visit_date');
            $table->unsignedTinyInteger('gestational_age_weeks')->default(0);
            $table->unsignedTinyInteger('gestational_age_days')->default(0);
            $table->decimal('fundal_height_cm', 5, 2)->nullable();
            $table->unsignedSmallInteger('fetal_heart_rate')->nullable();
            $table->string('presentation', 20)->nullable()
                ->comment('cephalic|breech|transverse|unknown');
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->unsignedSmallInteger('bp_systolic')->nullable();
            $table->unsignedSmallInteger('bp_diastolic')->nullable();
            $table->string('urine_protein', 10)->nullable()
                ->comment('negative|trace|1+|2+|3+|4+');
            $table->string('urine_glucose', 10)->nullable()
                ->comment('negative|trace|positive');
            $table->string('oedema', 10)->nullable()
                ->comment('none|mild|moderate|severe');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('pregnancy_record_id');
            $table->index('patient_id');
            $table->index('visit_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antenatal_visits');
    }
};
