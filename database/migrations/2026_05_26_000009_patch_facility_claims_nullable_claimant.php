<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make claimant_user_id nullable on facility_claims so that registry
     * entries can be linked to a facility without requiring a user account
     * (e.g. government-initiated claims or pre-claim registry linkage).
     *
     * Also fixes the facility_id FK to point to `facilities` (not `care_facilities`)
     * so that FacilityClaim::facility() correctly resolves to the Facility model.
     *
     * On SQLite (test environment), we recreate the table because SQLite does not
     * support DROP CONSTRAINT / ADD CONSTRAINT via ALTER TABLE.
     * On PostgreSQL (production), we use ALTER COLUMN and manipulate the FK.
     */
    public function up(): void
    {
        // Critical #1: Guard against missing table on fresh installs.
        if (!Schema::hasTable('facility_claims')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: recreate the table with the corrected schema
            DB::statement('PRAGMA foreign_keys = OFF');
            Schema::dropIfExists('facility_claims');
            Schema::create('facility_claims', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_id');
                $table->uuid('claimant_user_id')->nullable();
                $table->string('claim_status')->default('submitted');
                $table->text('claim_reason')->nullable();
                $table->timestamp('submitted_at')->useCurrent();
                $table->uuid('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();

                $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
                $table->foreign('claimant_user_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            });
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            // PostgreSQL / MySQL: patch in place, wrapped in a transaction for atomicity.
            // Critical #3: Wrap all ALTER TABLE statements in a transaction to prevent
            // partial failures leaving the schema in an inconsistent state.
            DB::transaction(function () {
                // Critical #2: Drop old FK to care_facilities if it exists, then add FK
                // to facilities only if it does not already exist — making this idempotent.
                // 1. Drop the old FK constraint on facility_id (points to care_facilities)
                DB::statement('ALTER TABLE facility_claims DROP CONSTRAINT IF EXISTS facility_claims_facility_id_foreign');
                // 2. Add new FK pointing to facilities only if it doesn't already exist
                DB::statement("
                    DO $$
                    BEGIN
                        IF NOT EXISTS (
                            SELECT 1 FROM information_schema.table_constraints
                            WHERE table_name = 'facility_claims'
                              AND constraint_name = 'facility_claims_facility_id_foreign'
                              AND constraint_type = 'FOREIGN KEY'
                        ) THEN
                            ALTER TABLE facility_claims
                                ADD CONSTRAINT facility_claims_facility_id_foreign
                                FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE;
                        END IF;
                    END
                    $$
                ");
                // 3. Drop and re-add FK for claimant_user_id to make it nullable + set null on delete
                DB::statement('ALTER TABLE facility_claims DROP CONSTRAINT IF EXISTS facility_claims_claimant_user_id_foreign');
                // 4. Make claimant_user_id nullable
                Schema::table('facility_claims', function (Blueprint $table) {
                    $table->uuid('claimant_user_id')->nullable()->change();
                    $table->text('claim_reason')->nullable()->change();
                    $table->timestamp('submitted_at')->nullable()->change();
                });
                DB::statement('ALTER TABLE facility_claims ADD CONSTRAINT facility_claims_claimant_user_id_foreign FOREIGN KEY (claimant_user_id) REFERENCES users(id) ON DELETE SET NULL');
            });
        }
    }

    public function down(): void
    {
        // Revert is complex; for safety we leave the schema as-is on down()
        // Production rollback should be done manually if needed.
    }
};
