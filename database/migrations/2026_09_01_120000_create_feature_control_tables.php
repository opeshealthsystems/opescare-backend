<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Module & Feature Control Centre.
 *
 * config/features.php stays the DEFAULT — the state a capability has when
 * nothing has overridden it, and the state the platform falls back to if these
 * tables are unreadable. That fallback matters: it is what keeps the control
 * plane fail-closed. A database problem must never open a frozen module.
 *
 * `feature_states` holds the current platform-level state plus any scheduled
 * transition. `feature_state_changes` is the append-only record of who changed
 * what, when, and why — never updated, never deleted.
 *
 * The organization layer already exists as `module_entitlements`; this
 * migration deliberately does not duplicate it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_states', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // The capability key, matching config('features.flags.*').
            $table->string('feature_key')->unique();

            // live | pilot | frozen | disabled
            $table->string('state', 16);

            // A scheduled transition: move to `scheduled_state` at
            // `scheduled_for`. Both null when nothing is scheduled.
            $table->string('scheduled_state', 16)->nullable();
            $table->timestampTz('scheduled_for')->nullable();

            // For a temporary pilot or a timed disable: revert at this moment.
            $table->timestampTz('expires_at')->nullable();
            $table->string('expiry_state', 16)->nullable();

            $table->uuid('changed_by')->nullable();
            $table->text('reason')->nullable();
            $table->timestampTz('effective_at')->nullable();

            $table->timestamps();

            $table->index('state');
            $table->index('scheduled_for');
            $table->index('expires_at');
        });

        Schema::create('feature_state_changes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('feature_key');
            $table->string('previous_state', 16)->nullable();
            $table->string('new_state', 16);

            // Why. Required by the control centre — a state change with no
            // stated reason is not auditable after the fact.
            $table->text('reason');

            $table->uuid('changed_by')->nullable();
            $table->string('changed_by_email')->nullable();
            $table->string('changed_by_role')->nullable();

            // Request context, for a security review of who did what from where.
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();

            // Scope of the change: 'platform' or a specific organization.
            $table->string('scope', 32)->default('platform');
            $table->uuid('organization_id')->nullable();

            // 'all organizations', or a count/list where scoped.
            $table->text('affected')->nullable();

            $table->timestampTz('effective_at')->nullable();
            $table->timestampTz('expires_at')->nullable();

            // Whether this was applied immediately or scheduled for later.
            $table->boolean('was_scheduled')->default(false);

            $table->timestampTz('created_at')->nullable();

            $table->index('feature_key');
            $table->index('changed_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_state_changes');
        Schema::dropIfExists('feature_states');
    }
};
