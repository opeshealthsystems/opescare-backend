<?php
namespace Tests\Feature\Registry;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportFacilityRegistryCommandTest extends TestCase
{
    use RefreshDatabase;

    private function writeCsv(string $content): string
    {
        $path = storage_path('app/imports/test_facilities.csv');
        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);
        return $path;
    }

    public function test_imports_valid_csv(): void
    {
        $csv = "name,type,ownership,region,division,city,address,phone,email,website,ministry_code,accreditation_level,bed_capacity,gps_lat,gps_lng,services\n";
        $csv .= "Hôpital de Test,hospital,public,Centre,,Yaoundé,,,,,MIN-001,Hôpital de District,50,,,\n";

        $path = $this->writeCsv($csv);

        $this->artisan('registry:import-facilities', ['--file' => $path])
             ->assertSuccessful();

        $this->assertDatabaseHas('facility_registry', ['name' => 'Hôpital de Test', 'region' => 'Centre']);
    }

    public function test_dry_run_does_not_write(): void
    {
        $csv = "name,type,ownership,region,division,city,address,phone,email,website,ministry_code,accreditation_level,bed_capacity,gps_lat,gps_lng,services\n";
        $csv .= "Phantom Hospital,hospital,public,Littoral,,Douala,,,,,,,,\n";

        $path = $this->writeCsv($csv);

        $this->artisan('registry:import-facilities', ['--file' => $path, '--dry-run' => true])
             ->assertSuccessful();

        $this->assertDatabaseMissing('facility_registry', ['name' => 'Phantom Hospital']);
    }

    public function test_merge_mode_skips_claimed_rows(): void
    {
        $facility = \App\Models\Facility::create(['name' => 'Owned Hospital', 'type' => 'hospital']);
        \DB::table('facility_registry')->insert([
            'id'                  => (string) \Illuminate\Support\Str::uuid(),
            'name'                => 'Owned Hospital',
            'type'                => 'hospital',
            'region'              => 'Centre',
            'city'                => 'Yaoundé',
            'status'              => 'verified',
            'claimed_facility_id' => $facility->id,
            'source'              => 'test',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $csv = "name,type,ownership,region,division,city,address,phone,email,website,ministry_code,accreditation_level,bed_capacity,gps_lat,gps_lng,services\n";
        $csv .= "Owned Hospital,hospital,public,Centre,,Yaoundé,,,,,,,,,\n";

        $path = $this->writeCsv($csv);

        $this->artisan('registry:import-facilities', ['--file' => $path])
             ->assertSuccessful();

        // Status should remain 'verified' — claimed rows are never overwritten
        $this->assertDatabaseHas('facility_registry', ['name' => 'Owned Hospital', 'status' => 'verified']);
    }

    public function test_rejects_invalid_type(): void
    {
        $csv = "name,type,ownership,region,division,city,address,phone,email,website,ministry_code,accreditation_level,bed_capacity,gps_lat,gps_lng,services\n";
        $csv .= "Bad Facility,invalid_type,public,Centre,,Yaoundé,,,,,,,,\n";

        $path = $this->writeCsv($csv);

        $this->artisan('registry:import-facilities', ['--file' => $path])
             ->assertSuccessful(); // command exits OK but reports error

        $this->assertDatabaseMissing('facility_registry', ['name' => 'Bad Facility']);
    }

    public function test_replace_mode_only_deletes_unclaimed_rows(): void
    {
        // Seed an unclaimed row
        \DB::table('facility_registry')->insert([
            'id'         => (string) \Illuminate\Support\Str::uuid(),
            'name'       => 'Stale Hospital',
            'type'       => 'hospital',
            'region'     => 'Centre',
            'city'       => 'Yaoundé',
            'status'     => 'unverified',
            'source'     => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed a claimed row
        $facility = \App\Models\Facility::create(['name' => 'Claimed Hospital', 'type' => 'hospital']);
        \DB::table('facility_registry')->insert([
            'id'                  => (string) \Illuminate\Support\Str::uuid(),
            'name'                => 'Claimed Hospital',
            'type'                => 'hospital',
            'region'              => 'Centre',
            'city'                => 'Yaoundé',
            'status'              => 'verified',
            'claimed_facility_id' => $facility->id,
            'source'              => 'test',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Import with replace mode (empty CSV)
        $csv = "name,type,ownership,region,division,city,address,phone,email,website,ministry_code,accreditation_level,bed_capacity,gps_lat,gps_lng,services\n";
        $path = $this->writeCsv($csv);

        $this->artisan('registry:import-facilities', ['--file' => $path, '--mode' => 'replace'])
             ->assertSuccessful();

        // Unclaimed row should be deleted
        $this->assertDatabaseMissing('facility_registry', ['name' => 'Stale Hospital']);
        // Claimed row must survive
        $this->assertDatabaseHas('facility_registry', ['name' => 'Claimed Hospital', 'status' => 'verified']);
    }
}
