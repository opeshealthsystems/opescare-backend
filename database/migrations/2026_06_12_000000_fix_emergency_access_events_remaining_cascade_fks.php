<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Follow-up to 2026_05_25_204643 (Security finding H11): that migration
     * fixed only the patient_id FK, but emergency_access_events also has
     * facility_id and provider_id FKs created with ON DELETE CASCADE.
     * Emergency-access audit records must survive deletion of the facility
     * or provider as well — switch both FKs to SET NULL.
     *
     * Only runs on PostgreSQL; SQLite test env is skipped gracefully.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('emergency_access_events', function (Blueprint $table) {
            $table->uuid('facility_id')->nullable()->change();
            $table->uuid('provider_id')->nullable()->change();
        });

        DB::statement('ALTER TABLE emergency_access_events DROP CONSTRAINT IF EXISTS "emergency_access_events_facility_id_foreign"');
        DB::statement('
            ALTER TABLE emergency_access_events
            ADD CONSTRAINT emergency_access_events_facility_id_foreign
            FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE SET NULL
        ');

        DB::statement('ALTER TABLE emergency_access_events DROP CONSTRAINT IF EXISTS "emergency_access_events_provider_id_foreign"');
        DB::statement('
            ALTER TABLE emergency_access_events
            ADD CONSTRAINT emergency_access_events_provider_id_foreign
            FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE SET NULL
        ');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE emergency_access_events DROP CONSTRAINT IF EXISTS "emergency_access_events_facility_id_foreign"');
        DB::statement('
            ALTER TABLE emergency_access_events
            ADD CONSTRAINT emergency_access_events_facility_id_foreign
            FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
        ');

        DB::statement('ALTER TABLE emergency_access_events DROP CONSTRAINT IF EXISTS "emergency_access_events_provider_id_foreign"');
        DB::statement('
            ALTER TABLE emergency_access_events
            ADD CONSTRAINT emergency_access_events_provider_id_foreign
            FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE
        ');

        Schema::table('emergency_access_events', function (Blueprint $table) {
            $table->uuid('facility_id')->nullable(false)->change();
            $table->uuid('provider_id')->nullable(false)->change();
        });
    }
};
