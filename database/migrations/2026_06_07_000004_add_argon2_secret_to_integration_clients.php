<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration Sprint — Item 1: Client Secret Re-hashing (SHA-256 → Argon2)
 *
 * Adds `client_secret_argon` column alongside the existing `client_secret`
 * (SHA-256) column so the two can coexist during the rolling migration window.
 *
 * Rolling migration strategy (zero-downtime):
 *   Phase 1 — THIS MIGRATION: add new column, deploy new middleware code.
 *   Phase 2 — AUTOMATIC: every time an integration client authenticates
 *              successfully via SHA-256, the middleware re-hashes and writes
 *              the Argon2 hash into client_secret_argon.
 *   Phase 3 — FUTURE MIGRATION: once all clients have re-authenticated
 *              (client_secret_argon IS NOT NULL for every active row),
 *              drop the old client_secret column.
 *
 * ISO 27001 A.10.1: secrets must be stored with a key-derivation function
 * that has a configurable work factor (bcrypt, argon2id). SHA-256 has no
 * work factor — an attacker who obtains the DB can brute-force all secrets.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_clients', function (Blueprint $table) {
            // Argon2id hash — set by middleware on first successful auth after deploy.
            // Nullable because existing rows won't have it until they re-authenticate.
            $table->string('client_secret_argon', 255)
                ->nullable()
                ->after('client_secret')
                ->comment('Argon2id hash of client secret (replaces SHA-256 client_secret column).');

            // Track when this client was last re-hashed — useful for forced rotation audits.
            $table->timestamp('secret_upgraded_at')
                ->nullable()
                ->after('client_secret_argon')
                ->comment('When the secret was last upgraded from SHA-256 to Argon2id.');
        });
    }

    public function down(): void
    {
        Schema::table('integration_clients', function (Blueprint $table) {
            $table->dropColumn(['client_secret_argon', 'secret_upgraded_at']);
        });
    }
};
