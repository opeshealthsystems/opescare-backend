<?php
namespace Tests\Feature\Interoperability;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CnamgsTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_store_cnamgs_id_and_national_id(): void
    {
        $patient = Patient::factory()->create([
            'cnamgs_id'          => 'CM-1234-5678',
            'cnamgs_verified_at' => now(),
            'national_id_number' => '123456789',
            'national_id_type'   => 'cni',
        ]);

        $this->assertEquals('CM-1234-5678', $patient->fresh()->cnamgs_id);
        $this->assertEquals('cni', $patient->fresh()->national_id_type);
        $this->assertNotNull($patient->fresh()->cnamgs_verified_at);
    }

    public function test_cnamgs_id_is_unique(): void
    {
        Patient::factory()->create(['cnamgs_id' => 'CM-0001-0001']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Patient::factory()->create(['cnamgs_id' => 'CM-0001-0001']);
    }
}
