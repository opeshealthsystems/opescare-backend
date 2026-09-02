<?php
namespace Tests\Feature\Registry;

use App\Models\Facility;
use App\Models\FacilityRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilityRegistryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_table_exists_with_correct_columns(): void
    {
        $this->assertTrue(\Schema::hasTable('facility_registry'));
        foreach (['name','type','region','status','claimed_facility_id'] as $col) {
            $this->assertTrue(\Schema::hasColumn('facility_registry', $col), "Missing column: $col");
        }
    }

    public function test_scopes_work_correctly(): void
    {
        FacilityRegistry::create(['name' => 'Test Hospital', 'type' => 'hospital', 'region' => 'Centre', 'city' => 'Yaoundé']);
        FacilityRegistry::create(['name' => 'Test Pharmacy', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala']);

        $this->assertEquals(1, FacilityRegistry::byRegion('Centre')->count());
        $this->assertEquals(1, FacilityRegistry::byType('pharmacy')->count());
        $this->assertEquals(2, FacilityRegistry::unclaimed()->count());
        $this->assertEquals(2, FacilityRegistry::open()->count());
    }

    public function test_claimed_facility_relationship(): void
    {
        $facility = Facility::create(['name' => 'Real Hospital', 'type' => 'hospital']);
        $entry    = FacilityRegistry::create([
            'name'                => 'Real Hospital',
            'type'                => 'hospital',
            'region'              => 'Centre',
            'city'                => 'Yaoundé',
            'claimed_facility_id' => $facility->id,
            'claimed_at'          => now(),
        ]);

        $this->assertEquals($facility->id, $entry->claimedFacility->id);
        $this->assertEquals(0, FacilityRegistry::unclaimed()->count());
    }

    public function test_facility_claim_relationship_points_to_facility(): void
    {
        $facility = Facility::create(['name' => 'A Hospital', 'type' => 'hospital']);
        $claim    = \App\Models\FacilityClaim::create([
            'facility_id'      => $facility->id,
            'claimant_user_id' => null,
            // 'pending' was never one of this column's states — the migration
            // documents submitted|under_review|approved|rejected|revoked and the
            // column defaults to 'submitted'. App\Enums\FacilityClaimStatus now
            // says so in code.
            'claim_status'     => \App\Enums\FacilityClaimStatus::Submitted->value,
            'submitted_at'     => now(),
        ]);

        $this->assertInstanceOf(Facility::class, $claim->facility);
    }
}
