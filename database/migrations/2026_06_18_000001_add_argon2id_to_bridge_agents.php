<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX (audit 2026-06-18): Migrate Bridge Agent authentication from
     * plain SHA-256 to Argon2id, matching the IntegrationClient pattern.
     *
     * ISO 27001 A.10.1: Argon2id provides configurable memory/time cost
     * against brute-force attacks. SHA-256 has no work factor.
     *
     * Rolling upgrade:
     *   - New column agent_key_argon stores the Argon2id hash.
     *   - Existing agent_key (SHA-256) remains during migration window.
     *   - Middleware verifies Argon2id first, falls back to SHA-256 on miss.
     *   - On SHA-256 success: agent_key_argon is written, agent_key is cleared.
     */
    public function up(): void
    {
        Schema::table('bridge_agents', function (Blueprint $table) {
            $table->string('agent_key_argon', 255)->nullable()->after('agent_key')
                ->comment('Argon2id hash for agent authentication (replaces SHA-256 agent_key)');
            $table->timestamp('secret_upgraded_at')->nullable()->after('agent_key_argon')
                ->comment('When the agent secret was last upgraded to Argon2id');
        });
    }

    public function down(): void
    {
        Schema::table('bridge_agents', function (Blueprint $table) {
            $table->dropColumn(['agent_key_argon', 'secret_upgraded_at']);
        });
    }
};
