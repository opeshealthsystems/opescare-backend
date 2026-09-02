<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('opens_at');
            $table->time('closes_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
        });

        Schema::create('provider_availabilities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->uuid('provider_id')->index();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('appointment_slots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->uuid('provider_id')->index();
            $table->timestampTz('starts_at')->index();
            $table->timestampTz('ends_at');
            $table->unsignedInteger('capacity')->default(1);
            $table->unsignedInteger('booked_count')->default(0);
            $table->string('status')->default('open');
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('facility_id')->index();
            $table->uuid('provider_id')->nullable()->index();
            $table->uuid('appointment_slot_id')->nullable()->index();
            $table->uuid('visit_id')->nullable()->index();
            $table->uuid('rescheduled_from_appointment_id')->nullable()->index();
            $table->string('appointment_type');
            $table->string('status')->default('scheduled');
            $table->timestampTz('scheduled_at')->index();
            $table->string('booked_by_type')->nullable();
            $table->uuid('booked_by_id')->nullable()->index();
            $table->text('reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->uuid('cancelled_by_id')->nullable()->index();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampTz('checked_in_at')->nullable();
            $table->timestampTz('no_show_at')->nullable();
            $table->boolean('billing_deferred')->default(true);
            $table->boolean('telemedicine_deferred')->default(true);
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('appointment_slot_id')->references('id')->on('appointment_slots')->onDelete('set null');
            $table->foreign('visit_id')->references('id')->on('visits')->onDelete('set null');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('rescheduled_from_appointment_id')
                ->references('id')
                ->on('appointments')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('appointment_slots');
        Schema::dropIfExists('provider_availabilities');
        Schema::dropIfExists('facility_schedules');
    }
};
