<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_plan_interventions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('care_plan_id')->index();
            $table->enum('intervention_type', [
                'medication', 'exercise', 'diet', 'monitoring',
                'referral', 'education', 'other',
            ]);
            $table->text('description');
            $table->string('frequency', 100)->nullable();
            $table->string('responsible_party', 100)->nullable();
            $table->enum('status', ['active', 'completed', 'discontinued'])->default('active');
            $table->timestamps();

            $table->foreign('care_plan_id')->references('id')->on('care_plans')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_plan_interventions');
    }
};
