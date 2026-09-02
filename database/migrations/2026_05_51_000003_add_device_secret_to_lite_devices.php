<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add device_secret to lite_devices for HMAC-SHA256 request authentication.
 *
 * Each Lite device receives a 64-character random secret at registration time
 * (returned ONCE in the registration response). Subsequent requests must include:
 *
 *   X-Lite-Device-Id: <uuid>
 *   X-Lite-Timestamp: <unix timestamp>
 *   X-Lite-Signature: HMAC-SHA256(<device_id>.<timestamp>.<sha256(body)>, device_secret)
 *
 * The server validates the signature and rejects requests older than 5 minutes
 * (replay protection).
 *
 * Column is nullable to allow backward-compatibility with devices registered before
 * this migration. A null secret causes the server to log a warning but still allow
 * the request — rotate secrets during the first maintenance window post-deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lite_devices', function (Blueprint $table) {
            $table->string('device_secret', 64)->nullable()->after('allowed_modes')
                ->comment('HMAC secret for request authentication. Returned once at registration.');
        });
    }

    public function down(): void
    {
        Schema::table('lite_devices', function (Blueprint $table) {
            $table->dropColumn('device_secret');
        });
    }
};
