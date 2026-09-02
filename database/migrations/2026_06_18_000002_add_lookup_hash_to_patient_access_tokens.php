<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX (audit 2026-06-18): Add SHA-256 lookup hash column for patient access tokens.
     *
     * Previously, token lookup used a 12-character prefix (72 bits of entropy),
     * reducing the search space and making token enumeration theoretically possible.
     *
     * Now token_lookup_hash uses the full SHA-256 hash of the bearer token for
     * indexed DB lookups, while token_hash continues to use bcrypt/Argon2id for
     * constant-time comparison.
     *
     * Rolling migration:
     *   - New column: token_lookup_hash (nullable, unique, SHA-256 of full token)
     *   - Existing tokens will be upgraded on first use via the middleware fallback
     *   - token_prefix column is preserved (not dropped) for backward compatibility
     */
    public function up(): void
    {
        Schema::table('patient_access_tokens', function (Blueprint $table) {
            $table->string('token_lookup_hash', 64)->nullable()->unique()->after('token_hash')
                ->comment('SHA-256 hash of the full bearer token for efficient + secure DB lookup');
            
            // Add index on patient_id for quick revocation lookups
            $table->index('patient_id', 'patient_access_tokens_patient_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('patient_access_tokens', function (Blueprint $table) {
            $table->dropIndex('patient_access_tokens_patient_id_idx');
            $table->dropColumn('token_lookup_hash');
        });
    }
};
