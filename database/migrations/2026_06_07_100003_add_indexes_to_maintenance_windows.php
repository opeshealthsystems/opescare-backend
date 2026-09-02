<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add performance indexes to maintenance_windows.
 *
 * The CheckMaintenanceMode middleware queries:
 *   WHERE is_active = true AND starts_at <= now()
 *   AND (ends_at IS NULL OR ends_at > now())
 *
 * Without indexes this is a full table scan on every cache miss (every 5 min
 * per dyno/worker). The composite index on (is_active, starts_at) covers the
 * primary filter. The partial index on ends_at covers the expiry check.
 *
 * ProcessMaintenanceWindows also queries both columns every minute.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_windows', function (Blueprint $table) {
            // Composite: primary filter for active window lookup
            $table->index(['is_active', 'starts_at'], 'mw_active_starts_idx');

            // Partial filter for expiry check
            $table->index('ends_at', 'mw_ends_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_windows', function (Blueprint $table) {
            $table->dropIndex('mw_active_starts_idx');
            $table->dropIndex('mw_ends_at_idx');
        });
    }
};
