<?php
namespace Tests\Feature\Pharmacy;

use App\Models\ControlledSubstanceRecord;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlledSubstanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_record_controlled_substance_dispense(): void
    {
        $patient    = Patient::factory()->create();
        $prescriber = User::factory()->create();
        $dispenser  = User::factory()->create();
        $facility   = Facility::factory()->create();

        $prescription = Prescription::create([
            'patient_id'    => $patient->id,
            'prescribed_by' => $prescriber->id,
            'facility_id'   => $facility->id,
            'status'        => 'active',
        ]);

        $record = ControlledSubstanceRecord::create([
            'prescription_id'    => $prescription->id,
            'patient_id'         => $patient->id,
            'facility_id'        => $facility->id,
            'prescribed_by'      => $prescriber->id,
            'dispensed_by'       => $dispenser->id,
            'drug_name'          => 'Morphine Sulfate',
            'drug_schedule'      => 'schedule_2',
            'quantity_dispensed' => 30,
            'unit'               => 'tablet',
            'dispensed_at'       => now(),
            'batch_number'       => 'BATCH-2026-001',
        ]);

        $this->assertEquals('Morphine Sulfate', $record->drug_name);
        $this->assertEquals('schedule_2', $record->drug_schedule);
        $this->assertEquals(30, $record->quantity_dispensed);
    }

    public function test_cannot_dispense_without_valid_prescription(): void
    {
        $patient   = Patient::factory()->create();
        $dispenser = User::factory()->create();
        $facility  = Facility::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);

        ControlledSubstanceRecord::create([
            'prescription_id'    => '00000000-0000-0000-0000-000000000000',
            'patient_id'         => $patient->id,
            'facility_id'        => $facility->id,
            'prescribed_by'      => $dispenser->id,
            'dispensed_by'       => $dispenser->id,
            'drug_name'          => 'Tramadol',
            'drug_schedule'      => 'schedule_4',
            'quantity_dispensed' => 20,
            'unit'               => 'tablet',
            'dispensed_at'       => now(),
        ]);
    }

    public function test_controlled_substance_audit_trail_is_immutable(): void
    {
        $patient    = Patient::factory()->create();
        $prescriber = User::factory()->create();
        $dispenser  = User::factory()->create();
        $facility   = Facility::factory()->create();

        $prescription = Prescription::create([
            'patient_id'    => $patient->id,
            'prescribed_by' => $prescriber->id,
            'facility_id'   => $facility->id,
            'status'        => 'active',
        ]);

        $record = ControlledSubstanceRecord::create([
            'prescription_id'    => $prescription->id,
            'patient_id'         => $patient->id,
            'facility_id'        => $facility->id,
            'prescribed_by'      => $prescriber->id,
            'dispensed_by'       => $dispenser->id,
            'drug_name'          => 'Diazepam',
            'drug_schedule'      => 'schedule_4',
            'quantity_dispensed' => 10,
            'unit'               => 'tablet',
            'dispensed_at'       => now(),
        ]);

        $this->expectException(\LogicException::class);
        $record->update(['quantity_dispensed' => 5]);
    }
}
