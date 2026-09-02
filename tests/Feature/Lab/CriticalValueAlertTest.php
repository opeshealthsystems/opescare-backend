<?php
namespace Tests\Feature\Lab;

use App\Models\CriticalValueAlert;
use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\User;
use App\Services\Lab\CriticalValueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CriticalValueAlertTest extends TestCase
{
    use RefreshDatabase;

    private function makeLabResult(float $value, string $loincCode = '2823-3'): LabResult
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $order = LabOrder::create([
            'patient_id'  => $patient->id,
            'ordered_by'  => $provider->id,
            'facility_id' => $facility->id,
            'test_name'   => 'Serum Potassium',
            'urgency'     => 'routine',
            'status'      => 'resulted',
        ]);

        return LabResult::create([
            'lab_order_id'   => $order->id,
            'patient_id'     => $patient->id,
            'parameter_name' => 'Serum Potassium',
            'value'          => (string) $value,
            'unit'           => 'mmol/L',
            'loinc_code'     => $loincCode,
        ]);
    }

    public function test_critical_low_potassium_generates_alert(): void
    {
        $service = new CriticalValueService();
        $result  = $this->makeLabResult(2.5); // < 3.0 is critical low

        $alert = $service->evaluateResult($result);

        $this->assertNotNull($alert);
        $this->assertEquals('critical_low', $alert->alert_type);
        $this->assertFalse($alert->acknowledged);
    }

    public function test_normal_value_generates_no_alert(): void
    {
        $service = new CriticalValueService();
        $result  = $this->makeLabResult(4.2); // normal

        $alert = $service->evaluateResult($result);

        $this->assertNull($alert);
    }

    public function test_critical_alert_can_be_acknowledged(): void
    {
        $provider = User::factory()->create();
        $service  = new CriticalValueService();
        $result   = $this->makeLabResult(2.5);

        $alert = $service->evaluateResult($result);
        $this->assertNotNull($alert);

        $service->acknowledgeAlert($alert->id, $provider->id, 'Patient notified and IV potassium ordered');

        $alert->refresh();
        $this->assertTrue($alert->acknowledged);
        $this->assertEquals($provider->id, $alert->acknowledged_by);
        $this->assertNotNull($alert->acknowledged_at);
    }
}
