<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_queues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->string('name');
            $table->string('display_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->unique(['facility_id', 'name']);
        });

        Schema::create('patient_check_ins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('facility_id')->index();
            $table->uuid('visit_id')->nullable()->index();
            $table->uuid('appointment_id')->nullable()->index();
            $table->uuid('checked_in_by_id')->nullable()->index();
            $table->string('check_in_type')->default('walk_in');
            $table->timestampTz('checked_in_at')->useCurrent();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('visit_id')->references('id')->on('visits')->onDelete('set null');
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('set null');
            $table->foreign('checked_in_by_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('facility_id')->index();
            $table->uuid('visit_id')->nullable()->index();
            $table->uuid('appointment_id')->nullable()->index();
            $table->uuid('patient_check_in_id')->nullable()->index();
            $table->uuid('assigned_to_id')->nullable()->index();
            $table->string('queue_number');
            $table->string('current_queue')->index();
            $table->string('status')->default('waiting')->index();
            $table->unsignedTinyInteger('priority_level')->default(5)->index();
            $table->text('priority_reason')->nullable();
            $table->text('status_reason')->nullable();
            $table->timestampTz('checked_in_at')->useCurrent();
            $table->timestampTz('called_at')->nullable();
            $table->timestampTz('service_started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('visit_id')->references('id')->on('visits')->onDelete('set null');
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('set null');
            $table->foreign('patient_check_in_id')->references('id')->on('patient_check_ins')->onDelete('set null');
            $table->foreign('assigned_to_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['facility_id', 'queue_number']);
        });

        Schema::create('patient_flow_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('queue_ticket_id')->index();
            $table->uuid('patient_id')->index();
            $table->uuid('facility_id')->index();
            $table->uuid('visit_id')->nullable()->index();
            $table->uuid('actor_id')->nullable()->index();
            $table->string('event_type');
            $table->string('from_queue')->nullable();
            $table->string('to_queue')->nullable();
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('reason')->nullable();
            $table->timestampTz('occurred_at')->useCurrent();
            $table->timestamps();

            $table->foreign('queue_ticket_id')->references('id')->on('queue_tickets')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('visit_id')->references('id')->on('visits')->onDelete('set null');
            $table->foreign('actor_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_flow_events');
        Schema::dropIfExists('queue_tickets');
        Schema::dropIfExists('patient_check_ins');
        Schema::dropIfExists('facility_queues');
    }
};
