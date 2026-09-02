<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add schema_version to webhook events so consumers can pin to / detect the
 * payload contract version. Webhook payloads now carry a top-level
 * "schema_version" and deliveries include an X-OpesCare-Webhook-Version header.
 * See docs/API-VERSIONING.md. Defaults to 'v1' for all existing rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('webhook_events') && ! Schema::hasColumn('webhook_events', 'schema_version')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                $table->string('schema_version')->default('v1')->after('event_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('webhook_events') && Schema::hasColumn('webhook_events', 'schema_version')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                $table->dropColumn('schema_version');
            });
        }
    }
};
