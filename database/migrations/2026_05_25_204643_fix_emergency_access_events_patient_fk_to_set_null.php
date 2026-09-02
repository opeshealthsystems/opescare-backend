<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Change the emergency_access_events patient_id FK from CASCADE to SET NULL.
     *
     * Audit records must survive patient deletion (Security finding H11).
     * Only runs on PostgreSQL; SQLite test env is skipped gracefully.
     */
    public function up(): void
    {
        // Only run on PostgreSQL (production) — SQLite doesn't support FK constraint changes
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // The patient_id column must be nullable to support SET NULL
        Schema::table('emergency_access_events', function (Blueprint $table) {
            $table->uuid('patient_id')->nullable()->change();
        });

        // Drop the existing CASCADE FK
        DB::statement('ALTER TABLE emergency_access_events DROP CONSTRAINT IF EXISTS "emergency_access_events_patient_id_foreign"');

        // Re-add with SET NULL
        DB::statement('
            ALTER TABLE emergency_access_events
            ADD CONSTRAINT emergency_access_events_patient_id_foreign
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
        ');
    }

    /**
     * Reverse: restore CASCADE and make patient_id non-nullable again.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE emergency_access_events DROP CONSTRAINT IF EXISTS "emergency_access_events_patient_id_foreign"');

        DB::statement('
            ALTER TABLE emergency_access_events
            ADD CONSTRAINT emergency_access_events_patient_id_foreign
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
        ');

        Schema::table('emergency_access_events', function (Blueprint $table) {
            $table->uuid('patient_id')->nullable(false)->change();
        });
    }
};
