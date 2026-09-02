<?php
namespace Tests\Feature\Clinical;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\PharmacyRoute;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacyRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_pharmacy_route(): void
    {
        $facility = Facility::factory()->create();

        $route = PharmacyRoute::create([
            'facility_id'    => $facility->id,
            'pharmacy_name'  => 'Pharmacie Centrale Yaoundé',
            'pharmacy_type'  => 'in_facility',
            'contact_email'  => 'pharm@centraleyaounde.cm',
            'contact_phone'  => '+237 222 223 344',
            'routing_method' => 'fax',
            'is_active'      => true,
        ]);

        $this->assertEquals('in_facility', $route->pharmacy_type);
        $this->assertTrue($route->is_active);
    }

    public function test_prescription_can_be_routed(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $route = PharmacyRoute::create([
            'facility_id'    => $facility->id,
            'pharmacy_name'  => 'External Pharmacy',
            'pharmacy_type'  => 'external',
            'routing_method' => 'api',
            'is_active'      => true,
        ]);

        $prescription = Prescription::create([
            'patient_id'        => $patient->id,
            'prescribed_by'     => $provider->id,
            'facility_id'       => $facility->id,
            'pharmacy_route_id' => $route->id,
            'status'            => 'pending',
        ]);

        $this->assertEquals($route->id, $prescription->pharmacy_route_id);
    }
}
