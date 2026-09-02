<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Health ID Expiration / Renewal Scaffold
 *
 * Adds two nullable timestamp columns to the `patients` table:
 *
 *  - expires_at            The date the Health ID becomes invalid. NULL = never expires
 *                          (legacy records created before this feature). New IDs issued
 *                          after this migration should have this set to issued_at + 10 years
 *                          per MINSANTE Digital Health Strategy 2026–2030.
 *
 *  - renewal_required_at   The date from which renewal notices begin. Typically set to
 *                          expires_at - 90 days so the patient gets three monthly reminders
 *                          before expiry. NULL until populated by the expiry scaffold command.
 *
 * The `NotifyExpiringHealthIds` Artisan command queries patients where
 * `renewal_required_at <= now()` and `expires_at > now()` and dispatches
 * a notification to each. It should be scheduled daily.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Place after `verification_status` if it exists; otherwise just append.
            $table->timestamp('expires_at')->nullable()->after('verification_status')
                ->comment('Date the Health ID becomes invalid. NULL = no expiry (legacy).');

            $table->timestamp('renewal_required_at')->nullable()->after('expires_at')
                ->comment('Date renewal notices begin (typically expires_at - 90 days).');

            // Index for the daily expiry scan: only look at records that are
            // approaching expiry and haven't yet expired.
            $table->index(['renewal_required_at', 'expires_at'], 'idx_patients_renewal_window');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('idx_patients_renewal_window');
            $table->dropColumn(['expires_at', 'renewal_required_at']);
        });
    }
};
