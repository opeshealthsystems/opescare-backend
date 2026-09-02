<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Provenance for the patient-facing blood signal.
 *
 * `blood_availability` is what the Blood Finder answers with, and it carried
 * no record of WHO said so. Every other public availability table already does
 * — `pharmacy_stock_availability.source_system` and
 * `medicine_pharmacy_stocks.source_system`, where
 * MedicinePharmacyStock::scopeReportedByRealSource() uses it to withhold
 * seeded and unattributed rows from patients. Blood is the one place where
 * getting that wrong sends somebody across a city during a haemorrhage, and it
 * was the one place with no column to decide it on.
 *
 * Nullable, with no backfill and no default, on purpose:
 *
 *   - NULL means UNATTRIBUTED — nobody has claimed this row. It is withheld
 *     from public results by App\Models\BloodAvailability::scopeReportedByRealSource(),
 *     exactly as an unattributed medicine row is. Backfilling the existing
 *     rows to some plausible source would be inventing the very attribution
 *     the column exists to record.
 *   - A DEFAULT would make every future writer look attributed for free, which
 *     is the loophole this closes. Writers stamp explicitly:
 *     BloodAvailabilityProjector stamps every row it publishes, and
 *     DemoBloodInventorySeeder stamps its rows 'demo_seed'.
 *
 * Existing rows therefore go dark in the finder until a real source republishes
 * them. That is the intended outcome: today every row in this table came from
 * DemoBloodInventorySeeder, because there was no reachable write path at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blood_availability', function (Blueprint $table) {
            // portal | blood_inventory | partner-api | bridge-agent | demo_seed | seed
            $table->string('source_system')->nullable()->after('emergency_contact');
        });

        // The public search filters on it alongside group/component/status,
        // so it belongs in the same index shape those queries already use.
        Schema::table('blood_availability', function (Blueprint $table) {
            $table->index(['blood_group', 'component_type', 'availability_status', 'source_system'], 'blood_avail_public_search_idx');
        });
    }

    public function down(): void
    {
        Schema::table('blood_availability', function (Blueprint $table) {
            $table->dropIndex('blood_avail_public_search_idx');
            $table->dropColumn('source_system');
        });
    }
};
