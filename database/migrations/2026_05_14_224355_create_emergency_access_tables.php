<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_access_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('facility_id')->index();
            $table->uuid('provider_id')->index();
            $table->text('reason');
            $table->jsonb('records_viewed')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('emergency_review_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('emergency_access_event_id')->index();
            $table->string('status')->default('pending'); // pending, approved, escalated
            $table->uuid('reviewed_by')->nullable()->index();
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('emergency_access_event_id')->references('id')->on('emergency_access_events')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_review_cases');
        Schema::dropIfExists('emergency_access_events');
    }
};
