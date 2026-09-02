<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lite devices registered to facilities
        Schema::create('lite_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->string('device_name');
            $table->string('device_fingerprint', 128)->unique();
            $table->string('environment')->default('production')->index(); // production|staging|test
            $table->string('status')->default('pending')->index();        // pending|active|suspended|revoked|lost
            $table->string('platform')->nullable();                       // web|pwa|tablet|flutter
            $table->string('os_info')->nullable();
            $table->string('app_version')->nullable();
            $table->uuid('authorized_by')->nullable()->index();
            $table->timestampTz('last_seen_at')->nullable()->index();
            $table->timestampTz('activated_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->text('revoke_reason')->nullable();
            $table->json('allowed_modes')->nullable(); // online|low-bandwidth|offline-limited
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
            $table->foreign('authorized_by')->references('id')->on('users')->nullOnDelete();
        });

        // Per-device config (modules allowed, UI settings)
        Schema::create('lite_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lite_device_id')->unique()->index();
            $table->json('allowed_modules');       // array of module keys
            $table->string('language')->default('en');
            $table->boolean('offline_allowed')->default(false);
            $table->boolean('low_bandwidth_mode')->default(false);
            $table->unsignedInteger('sync_interval_seconds')->default(300);
            $table->json('blocked_offline_actions')->nullable();
            $table->string('currency_code')->default('NGN');
            $table->json('extra_settings')->nullable();
            $table->timestampTz('config_updated_at')->nullable();
            $table->timestamps();

            $table->foreign('lite_device_id')->references('id')->on('lite_devices')->cascadeOnDelete();
        });

        // Module-level entitlements granted to a device
        Schema::create('lite_module_entitlements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lite_device_id')->index();
            $table->string('module_key')->index();
            $table->boolean('is_enabled')->default(true);
            $table->timestampTz('granted_at')->useCurrent();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['lite_device_id', 'module_key']);
            $table->foreign('lite_device_id')->references('id')->on('lite_devices')->cascadeOnDelete();
        });

        // Offline events captured while device had no connectivity
        Schema::create('lite_offline_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lite_device_id')->index();
            $table->uuid('facility_id')->index();
            $table->string('event_type')->index(); // patient_registration|vitals|consultation|prescription|billing|stock_update
            $table->string('client_id', 64)->index(); // client-generated idempotency key
            $table->json('payload');               // encrypted at rest recommended
            $table->string('status')->default('queued')->index(); // queued|processing|applied|rejected|conflict
            $table->text('reject_reason')->nullable();
            $table->timestampTz('captured_at');    // when captured offline
            $table->timestampTz('received_at')->nullable(); // when server received
            $table->timestampTz('applied_at')->nullable();
            $table->uuid('applied_by')->nullable()->index(); // system or staff
            $table->timestamps();

            $table->unique(['lite_device_id', 'client_id']);
            $table->foreign('lite_device_id')->references('id')->on('lite_devices')->cascadeOnDelete();
            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
        });

        // Sync jobs tracking pull/push operations
        Schema::create('lite_sync_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lite_device_id')->index();
            $table->string('direction')->index();  // push|pull
            $table->string('status')->default('pending')->index(); // pending|running|completed|failed
            $table->unsignedInteger('events_sent')->default(0);
            $table->unsignedInteger('events_applied')->default(0);
            $table->unsignedInteger('events_rejected')->default(0);
            $table->unsignedInteger('conflicts_created')->default(0);
            $table->text('error_message')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('lite_device_id')->references('id')->on('lite_devices')->cascadeOnDelete();
        });

        // Conflicts needing manual reconciliation
        Schema::create('lite_conflicts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lite_device_id')->index();
            $table->uuid('lite_offline_event_id')->index();
            $table->string('conflict_type')->index(); // duplicate|data_mismatch|permission|stale_record
            $table->json('server_version')->nullable();
            $table->json('device_version')->nullable();
            $table->string('status')->default('open')->index(); // open|resolved|dismissed
            $table->uuid('resolved_by')->nullable()->index();
            $table->text('resolution_note')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('lite_device_id')->references('id')->on('lite_devices')->cascadeOnDelete();
            $table->foreign('lite_offline_event_id')->references('id')->on('lite_offline_events')->cascadeOnDelete();
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lite_conflicts');
        Schema::dropIfExists('lite_sync_jobs');
        Schema::dropIfExists('lite_offline_events');
        Schema::dropIfExists('lite_module_entitlements');
        Schema::dropIfExists('lite_configs');
        Schema::dropIfExists('lite_devices');
    }
};
