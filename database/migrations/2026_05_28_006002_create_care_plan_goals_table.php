<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_plan_goals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('care_plan_id')->index();
            $table->text('goal_text');
            $table->date('target_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'achieved', 'abandoned'])->default('pending');
            $table->timestamp('achieved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('care_plan_id')->references('id')->on('care_plans')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_plan_goals');
    }
};
