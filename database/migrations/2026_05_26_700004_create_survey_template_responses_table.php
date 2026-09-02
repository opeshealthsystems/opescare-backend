<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('survey_template_responses')) {
            return;
        }
        Schema::create('survey_template_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('survey_template_id')->constrained('survey_templates')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->json('answers');
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_template_responses');
    }
};
