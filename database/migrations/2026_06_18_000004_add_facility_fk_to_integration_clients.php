<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX (audit 2026-06-18): Add proper UUID foreign key for facility_id and
     * created_by column to integration_clients table.
     *
     * The original migration (2026_05_17_000001) defined facility_id as a plain
     * string column with no FK constraint, risking orphaned records when facilities
     * are deleted. Also, the IntegrationClient model references 'created_by' in
     * $fillable but the column was never created in the migration.
     *
     * This migration:
     *   1. Changes facility_id from string to uuid type
     *   2. Adds foreign key constraint (SET NULL on delete)
     *   3. Adds created_by column (nullable, FK to users)
     */
    public function up(): void
    {
        // Step 1: Add created_by column
        if (!Schema::hasColumn('integration_clients', 'created_by')) {
            Schema::table('integration_clients', function (Blueprint $table) {
                $table->uuid('created_by')->nullable()->index()->after('contact_email');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Step 2: Add approved_at and approved_by columns if missing
        if (!Schema::hasColumn('integration_clients', 'approved_by')) {
            Schema::table('integration_clients', function (Blueprint $table) {
                $table->uuid('approved_by')->nullable()->index()->after('approved_at');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Step 3: Add last_used_at and request_count if missing
        if (!Schema::hasColumn('integration_clients', 'last_used_at')) {
            Schema::table('integration_clients', function (Blueprint $table) {
                $table->timestamp('last_used_at')->nullable()->after('approved_by');
            });
        }
        if (!Schema::hasColumn('integration_clients', 'request_count')) {
            Schema::table('integration_clients', function (Blueprint $table) {
                $table->integer('request_count')->default(0)->after('last_used_at');
            });
        }

        // Step 4: Add client_secret_argon for Argon2id rolling migration
        // (matching the pattern used in VerifyIntegrationClient middleware)
        if (!Schema::hasColumn('integration_clients', 'client_secret_argon')) {
            Schema::table('integration_clients', function (Blueprint $table) {
                $table->string('client_secret_argon', 255)->nullable()->after('client_secret')
                    ->comment('Argon2id hash for client secret (replaces SHA-256)');
                $table->timestamp('secret_upgraded_at')->nullable()->after('client_secret_argon')
                    ->comment('When the secret was last upgraded to Argon2id');
            });
        }

        // Step 5: Add name, description, contact_email if missing
        if (!Schema::hasColumn('integration_clients', 'name')) {
            Schema::table('integration_clients', function (Blueprint $table) {
                $table->string('name')->nullable()->after('status');
                $table->text('description')->nullable()->after('name');
                $table->string('contact_email')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        Schema::table('integration_clients', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['created_by']);
            $table->dropForeign(['approved_by']);

            // Drop columns (safe — only if they exist)
            $columns = ['created_by', 'approved_by', 'last_used_at', 'request_count',
                        'client_secret_argon', 'secret_upgraded_at',
                        'name', 'description', 'contact_email'];

            foreach ($columns as $col) {
                if (Schema::hasColumn('integration_clients', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
