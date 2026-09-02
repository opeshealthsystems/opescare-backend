<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_survey_id')->index();
            $table->string('question_key', 50);
            $table->string('question_text', 255);
            $table->enum('response_type', ['rating_5', 'rating_10', 'yes_no', 'text']);
            $table->integer('numeric_response')->nullable();
            $table->text('text_response')->nullable();
            $table->timestamps();

            $table->foreign('patient_survey_id')
                  ->references('id')->on('patient_surveys')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
