<?php
namespace Tests\Feature\Registry;

use Database\Seeders\CameroonInsuranceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CameroonInsuranceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_cnamgs(): void
    {
        $this->seed(CameroonInsuranceSeeder::class);
        $this->assertDatabaseHas('insurance_providers', ['code' => 'CNAMGS', 'country_code' => 'CM']);
    }

    public function test_seeds_all_15_insurers(): void
    {
        $this->seed(CameroonInsuranceSeeder::class);
        $this->assertEquals(15, \DB::table('insurance_providers')->where('country_code', 'CM')->count());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(CameroonInsuranceSeeder::class);
        $this->seed(CameroonInsuranceSeeder::class);
        $this->assertEquals(15, \DB::table('insurance_providers')->where('country_code', 'CM')->count());
    }
}
