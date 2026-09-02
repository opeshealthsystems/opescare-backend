<?php
namespace Tests\Feature\Billing;

use App\Models\ClaimSubmission;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\RemittanceAdvice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaimSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_claim_submission(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $claim = ClaimSubmission::create([
            'patient_id'      => $patient->id,
            'facility_id'     => $facility->id,
            'insurer_name'    => 'CNAMGS',
            'claim_number'    => 'CLM-2026-001',
            'service_date'    => '2026-06-01',
            'billed_amount'   => 150000,
            'diagnosis_codes' => ['E11.9', 'I10'],
            'status'          => 'submitted',
        ]);

        $this->assertEquals('submitted', $claim->status);
        $this->assertEquals(150000, $claim->billed_amount);
        $this->assertContains('E11.9', $claim->diagnosis_codes);
    }

    public function test_can_record_remittance(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $claim = ClaimSubmission::create([
            'patient_id'      => $patient->id,
            'facility_id'     => $facility->id,
            'insurer_name'    => 'CNAMGS',
            'claim_number'    => 'CLM-2026-002',
            'service_date'    => '2026-06-01',
            'billed_amount'   => 150000,
            'diagnosis_codes' => ['E11.9'],
            'status'          => 'submitted',
        ]);

        $remittance = RemittanceAdvice::create([
            'claim_submission_id' => $claim->id,
            'paid_amount'         => 120000,
            'adjustment_amount'   => 30000,
            'adjustment_reason'   => 'Deductible applied',
            'paid_on'             => '2026-07-01',
            'payment_reference'   => 'CNAMGS-PAY-001',
        ]);

        $claim->update(['status' => 'paid', 'paid_amount' => 120000]);

        $this->assertEquals(120000, $remittance->paid_amount);
        $this->assertEquals('paid', $claim->fresh()->status);
    }
}
