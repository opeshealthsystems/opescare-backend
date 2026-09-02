<?php
namespace Tests\Feature\Interoperability;

use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\ProblemList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoincSnomedTest extends TestCase
{
    use RefreshDatabase;

    public function test_lab_result_can_store_loinc_code(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $order = LabOrder::create([
            'patient_id'  => $patient->id,
            'ordered_by'  => $provider->id,
            'facility_id' => $facility->id,
            'test_name'   => 'Fasting Blood Glucose',
            'urgency'     => 'routine',
            'status'      => 'pending',
        ]);

        $result = LabResult::create([
            'lab_order_id'   => $order->id,
            'patient_id'     => $patient->id,
            'parameter_name' => 'Fasting Blood Glucose',
            'value'          => '5.6',
            'unit'           => 'mmol/L',
            'loinc_code'     => '1556-0',
            'loinc_display'  => 'Fasting glucose [Mass/volume] in Capillary blood',
        ]);

        $this->assertEquals('1556-0', $result->fresh()->loinc_code);
    }

    public function test_problem_can_store_snomed_code(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();

        $problem = ProblemList::create([
            'patient_id'     => $patient->id,
            'provider_id'    => $provider->id,
            'icd_code'       => 'E11.9',
            'icd_version'    => '10',
            'description'    => 'Type 2 diabetes mellitus',
            'snomed_code'    => '44054006',
            'snomed_display' => 'Diabetes mellitus type 2',
            'status'         => 'active',
            'priority'       => 'high',
        ]);

        $this->assertEquals('44054006', $problem->fresh()->snomed_code);
    }
}
