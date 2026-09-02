<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lifecycle messaging dedupe columns for the subscription lifecycle automation
 * (renewal reminders + win-back). Additive and non-destructive — both columns
 * are nullable timestamps with no default, so existing rows are unaffected.
 *
 * - renewal_reminded_at : last time a renewal reminder was queued for the
 *   subscription's CURRENT period. Reset semantics handled in the command
 *   (a reminder is re-sent only once the stored value predates the current
 *   period start).
 * - winback_sent_at : set once when a win-back message has been queued for a
 *   lapsed subscription, so we never re-pester the same lapse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_subscriptions', function (Blueprint $table) {
            $table->timestamp('renewal_reminded_at')->nullable()->after('auto_renew');
            $table->timestamp('winback_sent_at')->nullable()->after('renewal_reminded_at');
        });
    }

    public function down(): void
    {
        Schema::table('organization_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['renewal_reminded_at', 'winback_sent_at']);
        });
    }
};
