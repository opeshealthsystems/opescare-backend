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
        // Guard: care_facilities table may not exist yet when this migration runs
        // (table is created in 2026_05_25_000000). Column added there directly.
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
