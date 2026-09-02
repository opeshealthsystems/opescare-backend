<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Platform Settings ─────────────────────────────────────
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();         // e.g. 'platform.maintenance_mode', 'platform.default_language'
            $table->string('group')->default('general'); // general, security, notifications, billing, integrations
            $table->text('value')->nullable();
            $table->string('value_type')->default('string'); // string, boolean, integer, json
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);    // safe to expose in front-end config
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        // ── Feature Flags ─────────────────────────────────────────
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();         // e.g. 'feature.telemedicine', 'feature.offline_sync'
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(false);
            $table->string('scope')->default('global'); // global, country, organization, facility, user_group
            $table->string('scope_value')->nullable();  // country code, org id, facility id, etc.
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        // ── Module Toggles ────────────────────────────────────────
        Schema::create('module_toggles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('module_key');            // appointments, billing, telemedicine, etc.
            $table->string('module_label');
            $table->boolean('enabled')->default(true);
            $table->string('scope')->default('global');
            $table->string('scope_value')->nullable();
            $table->text('disable_reason')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['module_key', 'scope', 'scope_value']);
        });

        // ── Maintenance Windows ───────────────────────────────────
        Schema::create('maintenance_windows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('message')->nullable();     // shown to users during maintenance
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('allow_emergency_access')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        // ── Admin Action Log ──────────────────────────────────────
        Schema::create('admin_action_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('actor_id');
            $table->string('action');                // setting_updated, flag_toggled, module_toggled, maintenance_created, etc.
            $table->string('resource_type')->nullable();
            $table->string('resource_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('occurred_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_action_logs');
        Schema::dropIfExists('maintenance_windows');
        Schema::dropIfExists('module_toggles');
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('platform_settings');
    }
};
