<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration Sprint — Item 2: JWT Revocation / JTI Blacklist Table
 *
 * Creates the `revoked_tokens` table which is the persistent source of truth
 * for all revoked JWTs. The application layer caches each JTI in the Laravel
 * cache (fast path) and falls back to this table (slow path) on cache misses.
 *
 * Why a DB table and not just cache?
 *   - Survives cache flushes (deployment, Redis restart, cache:clear)
 *   - Provides a legal audit trail (ISO 27001 A.12.4: logging)
 *   - Allows cross-instance revocation in multi-server deployments
 *
 * Cleanup: nightly command `health-id:purge-revoked-tokens` removes rows
 * where expires_at < NOW() — the token is already expired so the revocation
 * entry is no longer needed.
 *
 * Index strategy:
 *   - PRIMARY on `jti` (UUID) for O(1) lookup
 *   - `idx_rt_expires_at` for efficient nightly cleanup (WHERE expires_at < ?)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revoked_tokens', function (Blueprint $table) {
            $table->uuid('jti')->primary();

            // When the original token expires — used for cleanup.
            // Once NOW() > expires_at, this revocation record is stale and can be purged.
            $table->timestamp('expires_at');

            $table->timestamp('revoked_at')->useCurrent();
            $table->string('revoked_by')->nullable()->comment('User ID or system that revoked this token.');
            $table->string('reason', 200)->nullable()->comment('Why this token was revoked.');
            $table->string('client_id')->nullable()->comment('The integration client that held this token.');

            $table->index('expires_at', 'idx_rt_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revoked_tokens');
    }
};
