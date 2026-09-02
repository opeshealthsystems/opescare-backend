<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Developer-portal apps (integration clients) are created by external developers
 * who are not tied to a facility, but integration_clients.facility_id was NOT
 * NULL — so app creation via the developer portal failed. Drop the NOT NULL
 * constraint directly (the column is varchar, so a Schema ->change() would try
 * to re-cast its type and fail on Postgres).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('integration_clients', 'facility_id')) {
            DB::statement('ALTER TABLE integration_clients ALTER COLUMN facility_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('integration_clients', 'facility_id')) {
            DB::statement('ALTER TABLE integration_clients ALTER COLUMN facility_id SET NOT NULL');
        }
    }
};
