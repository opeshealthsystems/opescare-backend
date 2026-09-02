<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pregnancy_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignUuid('provider_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('gravida')->default(1);
            $table->unsignedTinyInteger('para')->default(0);
            $table->date('edd')->nullable()->comment('Estimated delivery date');
            $table->date('lmp')->nullable()->comment('Last menstrual period');
            $table->string('pregnancy_status')->default('active')
                ->comment('active|delivered|miscarriage|stillbirth|ectopic|terminated');
            $table->string('blood_type', 3)->nullable()->comment('A|B|AB|O');
            $table->string('rhesus_factor', 10)->nullable()->comment('positive|negative');
            $table->boolean('high_risk')->default(false);
            $table->json('risk_factors')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('facility_id');
            $table->index('provider_id');
            $table->index('pregnancy_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pregnancy_records');
    }
};
