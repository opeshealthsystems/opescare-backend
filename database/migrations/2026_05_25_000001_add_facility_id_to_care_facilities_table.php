<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Column added directly in 2026_05_25_000000_create_care_map_tables.php.
        // This migration is a safe no-op on fresh installs.
        if (Schema::hasTable('care_facilities') && !Schema::hasColumn('care_facilities', 'facility_id')) {
            Schema::table('care_facilities', function (Blueprint $table) {
                $table->uuid('facility_id')->nullable()->after('organization_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('care_facilities', function (Blueprint $table) {
            $table->dropColumn('facility_id');
        });
    }
};
