<?php
namespace Tests\Feature\Registry;

use Database\Seeders\CameroonFacilityRegistrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CameroonFacilityRegistrySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_seeds_centre_region_hospitals(): void
    {
        $this->seed(CameroonFacilityRegistrySeeder::class);

        $this->assertDatabaseHas('facility_registry', [
            'name'   => 'Hôpital Central de Yaoundé',
            'region' => 'Centre',
            'type'   => 'hospital',
        ]);
        $this->assertDatabaseHas('facility_registry', [
            'name'   => 'CHU de Yaoundé',
            'region' => 'Centre',
        ]);
    }

    public function test_seeder_seeds_littoral_region_hospitals(): void
    {
        $this->seed(CameroonFacilityRegistrySeeder::class);

        $this->assertDatabaseHas('facility_registry', [
            'name'   => 'Hôpital Général de Douala',
            'region' => 'Littoral',
        ]);
        $this->assertDatabaseHas('facility_registry', [
            'name'   => 'Hôpital Laquintinie de Douala',
            'region' => 'Littoral',
        ]);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(CameroonFacilityRegistrySeeder::class);
        $countAfterFirst = \DB::table('facility_registry')->count();

        $this->seed(CameroonFacilityRegistrySeeder::class);
        $countAfterSecond = \DB::table('facility_registry')->count();

        $this->assertEquals($countAfterFirst, $countAfterSecond);
    }

    public function test_all_entries_have_required_fields(): void
    {
        $this->seed(CameroonFacilityRegistrySeeder::class);

        $invalid = \DB::table('facility_registry')
            ->whereNull('name')
            ->orWhereNull('type')
            ->orWhereNull('region')
            ->count();

        $this->assertEquals(0, $invalid, 'Some registry entries are missing required fields');
    }
}
