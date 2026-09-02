<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Services\FacilityCodeGenerator;

/**
 * Add facility_code to care_facilities.
 *
 * facility_code is a human-readable identifier in the format:
 *   OP-[REGION_CODE]-FID-[XXXX]
 *
 * Examples: OP-LT-FID-1825, OP-NW-FID-0410, OP-SW-FID-4507
 *
 * It is NOT the system PK (UUID). All FK relationships continue to use
 * care_facilities.id (UUID). facility_code is for display, search,
 * and patient/staff-facing identification only.
 *
 * Existing rows are backfilled using their stored region during up().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('care_facilities', function (Blueprint $table) {
            $table->string('facility_code', 20)
                ->nullable()
                ->unique()
                ->after('id')
                ->comment('Human-readable facility ID: OP-[REGION]-FID-[XXXX]');
        });

        // Backfill existing rows
        $facilities = DB::table('care_facilities')
            ->whereNull('facility_code')
            ->get(['id', 'region']);

        foreach ($facilities as $facility) {
            $region = $facility->region ?? 'XX';
            $code   = FacilityCodeGenerator::generate($region);

            DB::table('care_facilities')
                ->where('id', $facility->id)
                ->update(['facility_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('care_facilities', function (Blueprint $table) {
            $table->dropUnique(['facility_code']);
            $table->dropColumn('facility_code');
        });
    }
};
