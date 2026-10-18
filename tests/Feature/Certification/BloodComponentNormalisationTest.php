<?php

namespace Tests\Feature\Certification;

use App\Enums\BloodComponentType;
use App\Models\BloodRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Defect from wave R: a blood_requests row spelled `packed_red_cells` made the
 * staff queue throw a ValueError on the enum cast. The normalisation migration
 * rewrites operational spellings onto BloodComponentType values.
 */
class BloodComponentNormalisationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_spellings_are_rewritten_onto_the_enum(): void
    {
        $patient  = \App\Models\Patient::factory()->create();
        $facility = \App\Models\CareFacility::create([
            'facility_id' => \App\Models\Facility::factory()->create()->id,
            'facility_name' => 'Cert Blood Bank', 'facility_type' => 'blood_bank', 'listing_status' => 'active',
            'country_code' => 'CM', 'region' => 'Centre', 'city' => 'Yaounde',
            'address' => 'Rue 1', 'phone_primary' => '+237222234000',
        ])->id;

        $id = (string) Str::uuid();
        DB::table('blood_requests')->insert([
            'id' => $id, 'reference' => 'BR-CERT-1', 'patient_id' => $patient->id,
            'care_facility_id' => $facility, 'blood_group' => 'O+', 'component_type' => 'packed_red_cells',
            'quantity' => 1, 'urgency' => 'routine', 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        (require database_path('migrations/2026_10_18_120000_normalise_blood_component_spellings.php'))->up();

        $this->assertSame(BloodComponentType::RedCells, BloodRequest::find($id)->component_type);
    }
}
