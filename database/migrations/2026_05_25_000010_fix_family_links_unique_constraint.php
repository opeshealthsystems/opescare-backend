<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            // Drop the blanket unique constraint (Laravel's default naming convention)
            DB::statement('ALTER TABLE family_links DROP CONSTRAINT IF EXISTS family_links_guardian_user_id_dependent_patient_id_unique');

            // Add partial unique: only enforce uniqueness for non-terminal statuses
            DB::statement(
                "CREATE UNIQUE INDEX IF NOT EXISTS uq_family_links_active_pair
                 ON family_links (guardian_user_id, dependent_patient_id)
                 WHERE status NOT IN ('revoked', 'expired')"
            );
        }
        // SQLite: no change — partial unique indexes not supported
        // Application-level duplicate checking in sendInvite() prevents duplicates
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS uq_family_links_active_pair');
            DB::statement(
                'ALTER TABLE family_links ADD CONSTRAINT family_links_guardian_user_id_dependent_patient_id_unique
                 UNIQUE (guardian_user_id, dependent_patient_id)'
            );
        }
    }
};
