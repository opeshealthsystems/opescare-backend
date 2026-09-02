<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 46b — Bridge Agent & OpesCare Lite Models
 *
 * Adds:
 *   bridge_devices            — physical device running Bridge Agent
 *   bridge_pairing_codes      — one-time pairing codes
 *   bridge_connectors         — connector config per data source
 *   bridge_mappings           — field-level mapping rules per connector
 *   bridge_sync_jobs          — sync job runs
 *   bridge_sync_records       — per-record sync outcomes
 *   bridge_conflicts          — data conflicts during sync
 *   bridge_heartbeats         — liveness pings from Bridge Agent
 *   bridge_logs               — structured log entries from Bridge Agent
 *   bridge_versions           — installed Bridge Agent version registry
 *   lite_device_registrations — OpesCare Lite device onboarding records
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bridge_devices')) {
            Schema::create('bridge_devices', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('bridge_agent_id');
                $table->string('device_name');
                $table->string('hardware_id')->nullable();
                $table->string('os_type')->nullable();      // windows|linux|macos
                $table->string('os_version')->nullable();
                $table->string('status')->default('pending'); // pending|active|suspended|offline
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();

                $table->index('bridge_agent_id', 'bd_agent_idx');
                $table->index('status', 'bd_status_idx');
            });
        }

        if (! Schema::hasTable('bridge_pairing_codes')) {
            Schema::create('bridge_pairing_codes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('bridge_agent_id');
                $table->string('code');                    // one-time short code
                $table->string('status')->default('pending'); // pending|used|expired
                $table->timestamp('expires_at');
                $table->timestamp('used_at')->nullable();
                $table->timestamps();

                $table->unique('code', 'bpc_code_unique');
                $table->index('bridge_agent_id', 'bpc_agent_idx');
                $table->index('expires_at', 'bpc_expires_idx');
            });
        }

        if (! Schema::hasTable('bridge_connectors')) {
            Schema::create('bridge_connectors', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('bridge_agent_id');
                $table->string('connector_type');          // csv|hl7|fhir|db|api
                $table->string('name');
                $table->json('config')->nullable();        // connection params (encrypted at app layer)
                $table->string('status')->default('active'); // active|paused|error
                $table->timestamp('last_run_at')->nullable();
                $table->timestamps();

                $table->index('bridge_agent_id', 'bconn_agent_idx');
                $table->index('connector_type', 'bconn_type_idx');
            });
        }

        if (! Schema::hasTable('bridge_mappings')) {
            Schema::create('bridge_mappings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('bridge_connector_id');
                $table->string('source_field');
                $table->string('target_field');
                $table->string('transform')->nullable();   // none|trim|date_format|lookup
                $table->json('transform_params')->nullable();
                $table->boolean('is_required')->default(false);
                $table->timestamps();

                $table->index('bridge_connector_id', 'bmap_connector_idx');
            });
        }

        if (! Schema::hasTable('bridge_sync_jobs')) {
            Schema::create('bridge_sync_jobs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('bridge_connector_id');
                $table->string('direction');               // push|pull
                $table->string('status');                  // pending|running|completed|failed|partial
                $table->integer('records_total')->default(0);
                $table->integer('records_synced')->default(0);
                $table->integer('records_failed')->default(0);
                $table->integer('records_skipped')->default(0);
                $table->text('error_summary')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index('bridge_connector_id', 'bsj_connector_idx');
                $table->index('status', 'bsj_status_idx');
            });
        }

        if (! Schema::hasTable('bridge_sync_records')) {
            Schema::create('bridge_sync_records', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('bridge_sync_job_id');
                $table->string('external_id');
                $table->string('resource_type');
                $table->string('outcome');                 // created|updated|skipped|failed|conflict
                $table->uuid('internal_record_id')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index('bridge_sync_job_id', 'bsr_job_idx');
                $table->index('outcome', 'bsr_outcome_idx');
            });
        }

        if (! Schema::hasTable('bridge_conflicts')) {
            Schema::create('bridge_conflicts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('bridge_sync_job_id');
                $table->string('resource_type');
                $table->string('external_id');
                $table->uuid('internal_record_id')->nullable();
                $table->json('external_data')->nullable();
                $table->json('internal_data')->nullable();
                $table->string('conflict_type');           // duplicate|version_mismatch|invalid_data
                $table->string('status')->default('open'); // open|resolved|rejected
                $table->uuid('resolved_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index('bridge_sync_job_id', 'bc_job_idx');
                $table->index('status', 'bc_status_idx');
            });
        }

        if (! Schema::hasTable('bridge_heartbeats')) {
            Schema::create('bridge_heartbeats', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('bridge_agent_id');
                $table->uuid('bridge_device_id')->nullable();
                $table->string('agent_version')->nullable();
                $table->string('status');                  // healthy|degraded|error
                $table->json('metrics')->nullable();       // cpu|memory|queue_depth
                $table->timestamp('received_at');
                $table->timestamps();

                $table->index('bridge_agent_id', 'bhb_agent_idx');
                $table->index('received_at', 'bhb_received_idx');
            });
        }

        if (! Schema::hasTable('bridge_logs')) {
            Schema::create('bridge_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('bridge_agent_id');
                $table->string('level');                   // debug|info|warning|error|critical
                $table->string('component')->nullable();   // connector|sync|auth|mapping
                $table->text('message');
                $table->json('context')->nullable();
                $table->timestamp('logged_at');
                $table->timestamps();

                $table->index('bridge_agent_id', 'blog_agent_idx');
                $table->index(['level', 'logged_at'], 'blog_level_time_idx');
            });
        }

        if (! Schema::hasTable('bridge_versions')) {
            Schema::create('bridge_versions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('bridge_agent_id');
                $table->string('version');                 // semver e.g. 2.1.4
                $table->string('status');                  // current|outdated|deprecated|unsupported
                $table->timestamp('installed_at');
                $table->timestamp('deprecated_at')->nullable();
                $table->timestamps();

                $table->index('bridge_agent_id', 'bv_agent_idx');
                $table->index('version', 'bv_version_idx');
            });
        }

        // ── OpesCare Lite ─────────────────────────────────────────────────────

        if (! Schema::hasTable('lite_device_registrations')) {
            Schema::create('lite_device_registrations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('lite_device_id');
                $table->uuid('facility_id');
                $table->uuid('registered_by');
                $table->string('registration_code')->nullable();
                $table->string('status')->default('pending'); // pending|approved|rejected|revoked
                $table->uuid('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->index('lite_device_id', 'ldr_device_idx');
                $table->index('facility_id', 'ldr_facility_idx');
                $table->index('status', 'ldr_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lite_device_registrations');
        Schema::dropIfExists('bridge_versions');
        Schema::dropIfExists('bridge_logs');
        Schema::dropIfExists('bridge_heartbeats');
        Schema::dropIfExists('bridge_conflicts');
        Schema::dropIfExists('bridge_sync_records');
        Schema::dropIfExists('bridge_sync_jobs');
        Schema::dropIfExists('bridge_mappings');
        Schema::dropIfExists('bridge_connectors');
        Schema::dropIfExists('bridge_pairing_codes');
        Schema::dropIfExists('bridge_devices');
    }
};
