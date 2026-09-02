<?php
namespace Tests\Feature\Registry;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportInsuranceRegistryCommandTest extends TestCase
{
    use RefreshDatabase;

    private function writeCsv(string $content): string
    {
        $path = storage_path('app/imports/test_insurers.csv');
        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);
        return $path;
    }

    public function test_imports_valid_insurer_csv(): void
    {
        $csv = "name,code,country_code,contact_email,contact_phone,portal_url,api_endpoint,status\n";
        $csv .= "Test Insurer,TEST-CM,CM,test@test.cm,+237 000 000 000,,,active\n";

        $path = $this->writeCsv($csv);

        $this->artisan('registry:import-insurers', ['--file' => $path])
             ->assertSuccessful();

        $this->assertDatabaseHas('insurance_providers', ['code' => 'TEST-CM', 'country_code' => 'CM']);
    }

    public function test_dry_run_does_not_write(): void
    {
        $csv = "name,code,country_code,contact_email,contact_phone,portal_url,api_endpoint,status\n";
        $csv .= "Ghost Insurer,GHOST-CM,CM,ghost@ghost.cm,,,,active\n";

        $path = $this->writeCsv($csv);

        $this->artisan('registry:import-insurers', ['--file' => $path, '--dry-run' => true])
             ->assertSuccessful();

        $this->assertDatabaseMissing('insurance_providers', ['code' => 'GHOST-CM']);
    }

    public function test_upserts_by_code(): void
    {
        $csv = "name,code,country_code,contact_email,contact_phone,portal_url,api_endpoint,status\n";
        $csv .= "Original Name,UPS-CM,CM,a@a.cm,,,,active\n";
        $path = $this->writeCsv($csv);
        $this->artisan('registry:import-insurers', ['--file' => $path])->assertSuccessful();

        $csv2 = "name,code,country_code,contact_email,contact_phone,portal_url,api_endpoint,status\n";
        $csv2 .= "Updated Name,UPS-CM,CM,b@b.cm,,,,active\n";
        $path2 = $this->writeCsv($csv2);
        $this->artisan('registry:import-insurers', ['--file' => $path2])->assertSuccessful();

        $this->assertEquals(1, \DB::table('insurance_providers')->where('code', 'UPS-CM')->count());
        $this->assertDatabaseHas('insurance_providers', ['code' => 'UPS-CM', 'name' => 'Updated Name']);
    }
}
