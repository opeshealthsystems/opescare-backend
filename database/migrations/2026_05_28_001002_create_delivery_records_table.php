<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pregnancy_record_id')->constrained('pregnancy_records')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignUuid('provider_id')->constrained('users')->cascadeOnDelete();
            $table->date('delivery_date');
            $table->string('delivery_mode', 30)
                ->comment('svd|assisted_vaginal|caesarean|other');
            $table->text('indication')->nullable();
            $table->decimal('duration_labour_hours', 5, 2)->nullable();
            $table->unsignedSmallInteger('birth_weight_grams');
            $table->unsignedTinyInteger('apgar_1min')->nullable();
            $table->unsignedTinyInteger('apgar_5min')->nullable();
            $table->string('neonatal_outcome', 30)
                ->comment('live|stillbirth|early_neonatal_death');
            $table->text('complications')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('pregnancy_record_id');
            $table->index('patient_id');
            $table->index('delivery_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_records');
    }
};
