<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds registry_entry_id to facility_claims so a claim can reference which
 * facility_registry entry is being claimed (separate from facility_id which
 * references the operational Facility doing the claiming).
 *
 * When registry_entry_id is set (registry claim):
 *   On approval → stamps facility_registry.claimed_facility_id and auto-creates care_facilities entry.
 *
 * When registry_entry_id is null (original CareMap claim):
 *   On approval → updates care_facilities partner_id via care_facilities.facility_id link.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('facility_claims')) {
            return;
        }

        if (Schema::hasColumn('facility_claims', 'registry_entry_id')) {
            return;
        }

        Schema::table('facility_claims', function (Blueprint $table) {
            $table->uuid('registry_entry_id')->nullable()->after('facility_id');

            // FK only on databases that support it (not needed on SQLite for tests)
            if (config('database.default') !== 'sqlite') {
                $table->foreign('registry_entry_id')
                      ->references('id')
                      ->on('facility_registry')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('facility_claims')) {
            return;
        }

        Schema::table('facility_claims', function (Blueprint $table) {
            if (config('database.default') !== 'sqlite') {
                $table->dropForeign(['registry_entry_id']);
            }
            if (Schema::hasColumn('facility_claims', 'registry_entry_id')) {
                $table->dropColumn('registry_entry_id');
            }
        });
    }
};
