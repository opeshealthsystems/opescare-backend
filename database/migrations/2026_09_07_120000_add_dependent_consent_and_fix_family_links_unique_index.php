<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Family links — three fixes in one pass.
 *
 * 1. `dependent_consented_at`: the moment an adult dependent accepted the
 *    guardian's invitation. Until now the only trace of consent was
 *    `created_by = 'invite_accepted'`; a timestamp is what a consent record
 *    needs.
 *
 * 2. The partial unique index `uq_family_links_active_pair` excluded only
 *    `revoked` and `expired`. The mobile app cancels an invitation by setting
 *    `cancelled`, so cancel-then-reinvite hit 23505 and 500'd. Declining an
 *    invitation (new) writes `declined`. Both are terminal and are excluded now.
 *
 * 3. `access_level` vocabulary: the mobile invite wrote `view_only` and
 *    `guardian`, values the web never knew (it treated anything but
 *    `read_only` as full write access). Canonical pair is `full` | `read_only`;
 *    the model maps legacy values on read, and this migration makes the rows
 *    themselves say what they mean.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('family_links', 'dependent_consented_at')) {
            Schema::table('family_links', function (Blueprint $table) {
                $table->timestampTz('dependent_consented_at')->nullable()->after('age_transition_expires_at');
            });
        }

        // Rows that were accepted before this column existed: the acceptance
        // time is the last time the row changed state.
        DB::table('family_links')
            ->where('created_by', 'invite_accepted')
            ->whereNull('dependent_consented_at')
            ->update(['dependent_consented_at' => DB::raw('updated_at')]);

        DB::table('family_links')->where('access_level', 'view_only')->update(['access_level' => 'read_only']);
        DB::table('family_links')->where('access_level', 'guardian')->update(['access_level' => 'full']);

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS uq_family_links_active_pair');
            DB::statement(
                "CREATE UNIQUE INDEX IF NOT EXISTS uq_family_links_active_pair
                 ON family_links (guardian_user_id, dependent_patient_id)
                 WHERE status NOT IN ('revoked', 'expired', 'cancelled', 'declined')"
            );
        }
        // SQLite: no partial unique index — application-level duplicate checks apply.
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS uq_family_links_active_pair');
            DB::statement(
                "CREATE UNIQUE INDEX IF NOT EXISTS uq_family_links_active_pair
                 ON family_links (guardian_user_id, dependent_patient_id)
                 WHERE status NOT IN ('revoked', 'expired')"
            );
        }

        if (Schema::hasColumn('family_links', 'dependent_consented_at')) {
            Schema::table('family_links', function (Blueprint $table) {
                $table->dropColumn('dependent_consented_at');
            });
        }
        // The access_level normalisation is not reversed: the legacy spellings
        // carried no information the canonical pair does not.
    }
};
