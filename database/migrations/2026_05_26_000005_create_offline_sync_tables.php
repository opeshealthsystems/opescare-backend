<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_cache_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('facility_id')->nullable()->index();
            $table->string('device_id')->index();
            $table->json('allowed_scopes');
            $table->boolean('encryption_required')->default(true);
            $table->string('encryption_policy')->default('AES-256-GCM local database encryption required');
            $table->boolean('emergency_access')->default(false);
            $table->boolean('review_required')->default(false);
            $table->string('status')->default('active')->index();
            $table->timestampTz('expires_at')->nullable()->index();
            $table->uuid('created_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('facility_id')->references('id')->on('facilities')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('offline_queues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('local_cache_policy_id')->index();
            $table->uuid('patient_id')->index();
            $table->uuid('facility_id')->nullable()->index();
            $table->string('device_id')->index();
            $table->json('scopes');
            $table->longText('encrypted_payload');
            $table->string('payload_hash', 64)->index();
            $table->string('status')->default('queued')->index();
            $table->unsignedInteger('retry_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestampTz('next_retry_at')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->uuid('created_by')->nullable()->index();
            $table->timestamps();

            $table->unique(['local_cache_policy_id', 'payload_hash']);
            $table->foreign('local_cache_policy_id')->references('id')->on('local_cache_policies')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('facility_id')->references('id')->on('facilities')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('sync_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('offline_queue_id')->index();
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestampTz('last_attempted_at')->nullable();
            $table->timestamps();

            $table->foreign('offline_queue_id')->references('id')->on('offline_queues')->cascadeOnDelete();
        });

        Schema::create('sync_conflicts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('offline_queue_id')->index();
            $table->string('conflict_type');
            $table->string('status')->default('open')->index();
            $table->string('resolution_strategy')->nullable();
            $table->uuid('resolved_by')->nullable()->index();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('offline_queue_id')->references('id')->on('offline_queues')->cascadeOnDelete();
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('offline_audit_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('offline_queue_id')->nullable()->index();
            $table->uuid('local_cache_policy_id')->nullable()->index();
            $table->uuid('patient_id')->nullable()->index();
            $table->string('device_id')->nullable()->index();
            $table->string('event_type')->index();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('offline_queue_id')->references('id')->on('offline_queues')->cascadeOnDelete();
            $table->foreign('local_cache_policy_id')->references('id')->on('local_cache_policies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_audit_events');
        Schema::dropIfExists('sync_conflicts');
        Schema::dropIfExists('sync_jobs');
        Schema::dropIfExists('offline_queues');
        Schema::dropIfExists('local_cache_policies');
    }
};
