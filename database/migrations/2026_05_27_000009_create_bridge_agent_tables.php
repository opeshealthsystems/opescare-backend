<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Bridge Agent Registry ──────────────────────────────
        Schema::create('bridge_agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->string('name', 100);
            $table->string('agent_key', 80)->unique();   // SHA-256 stored key for auth
            $table->string('agent_key_prefix', 12);      // first 12 chars for display
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('version', 20)->nullable();   // agent software version
            $table->string('hostname', 150)->nullable();  // machine hostname
            $table->string('ip_address', 45)->nullable(); // last seen IP
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('capabilities')->nullable();     // ['ehr_sync','lab_sync','billing_sync']
            $table->text('notes')->nullable();
            $table->string('registered_by', 100)->nullable();
            $table->timestamps();
        });

        // ── Bridge Sync Batches ────────────────────────────────
        Schema::create('bridge_sync_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bridge_agent_id')->index();
            $table->uuid('facility_id')->index();
            $table->string('sync_type', 50);             // ehr_records, lab_results, appointments, billing
            $table->enum('status', ['received', 'processing', 'completed', 'failed'])->default('received');
            $table->integer('record_count')->default(0);
            $table->integer('inserted_count')->default(0);
            $table->integer('updated_count')->default(0);
            $table->integer('skipped_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->json('errors')->nullable();
            $table->string('checksum', 64)->nullable();  // SHA-256 of payload
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bridge_sync_batches');
        Schema::dropIfExists('bridge_agents');
    }
};
