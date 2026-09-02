<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX (audit 2026-06-18): Add is_demo tracking columns to audit_events table.
     *
     * The original audit_events migration (2026_05_14_215515) did not include
     * is_demo, demo_seed_key, or demo_reset_group columns. This made audit
     * events inconsistent with other tables (access_logs, security_incidents)
     * that do have these columns for demo data lifecycle management.
     *
     * During demo resets, audit_events for demo records could not be properly
     * identified and cleaned up.
     */
    public function up(): void
    {
        Schema::table('audit_events', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->index()->after('created_at');
            $table->string('demo_seed_key')->nullable()->index()->after('is_demo');
            $table->string('demo_reset_group')->nullable()->index()->after('demo_seed_key');
        });
    }

    public function down(): void
    {
        Schema::table('audit_events', function (Blueprint $table) {
            $table->dropIndex(['is_demo']);
            $table->dropIndex(['demo_seed_key']);
            $table->dropIndex(['demo_reset_group']);
            $table->dropColumn(['is_demo', 'demo_seed_key', 'demo_reset_group']);
        });
    }
};
