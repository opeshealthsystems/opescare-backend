<?php

use App\Modules\CareMap\Services\BloodAvailabilityProjector;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| `blood_requests.component_type` and `blood_availability.component_type` are
| cast to App\Enums\BloodComponentType (whole_blood, red_cells, platelets,
| plasma). Rows written before the projector normalised spellings carry the
| operational vocabulary (`packed_red_cells`, `fresh_frozen_plasma`, ...), and
| one such row made the staff blood-request queue throw a ValueError on
| render. Rewrite every alias onto its published value, using the projector's
| own map so there is one source of truth. A blood_availability alias whose
| published twin already exists for the same facility and group is left for
| the projector's next pass rather than colliding with it.
*/
return new class extends Migration {
    public function up(): void
    {
        $aliases = [];
        foreach (BloodAvailabilityProjector::operationalComponents() as $spelling) {
            $published = BloodAvailabilityProjector::publishedComponent($spelling);
            if ($published !== null && $published->value !== $spelling) {
                $aliases[$spelling] = $published->value;
            }
        }

        foreach ($aliases as $from => $to) {
            if (Schema::hasTable('blood_requests')) {
                DB::table('blood_requests')->where('component_type', $from)->update(['component_type' => $to]);
            }
            if (Schema::hasTable('blood_availability')) {
                DB::update(
                    'UPDATE blood_availability a SET component_type = ? WHERE a.component_type = ?
                       AND NOT EXISTS (SELECT 1 FROM blood_availability b WHERE b.facility_id = a.facility_id
                       AND b.blood_group = a.blood_group AND b.component_type = ?)',
                    [$to, $from, $to]
                );
            }
        }
    }

    public function down(): void
    {
        // Normalisation is one-way: the original spelling carried no meaning.
    }
};
