<?php
namespace Tests\Feature\Clinical;

use App\Models\DrugInteractionAlert;
use App\Models\Facility;
use App\Models\MedicationReconciliation;
use App\Models\Patient;
use App\Models\User;
use App\Modules\ClinicalDecisionSupport\Services\DrugInteractionService;
use App\Modules\OperationalFlow\Services\MedicationReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicationReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_reconciliation_record(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $service = new MedicationReconciliationService();
        $rec = $service->createReconciliation(
            patientId:   $patient->id,
            providerId:  $provider->id,
            facilityId:  $facility->id,
            medications: [
                ['name' => 'Metformin', 'dose' => '500mg', 'frequency' => 'BD', 'source' => 'patient_reported'],
                ['name' => 'Atorvastatin', 'dose' => '20mg', 'frequency' => 'OD', 'source' => 'pharmacy_record'],
            ],
            notes: 'Admission reconciliation'
        );

        $this->assertInstanceOf(MedicationReconciliation::class, $rec);
        $this->assertEquals($patient->id, $rec->patient_id);
        $this->assertCount(2, $rec->medications);
        $this->assertEquals('pending_review', $rec->status);
    }

    public function test_drug_interaction_check_detects_known_pair(): void
    {
        $service = new DrugInteractionService();

        $alerts = $service->checkInteractions([
            ['name' => 'Warfarin', 'dose' => '5mg'],
            ['name' => 'Aspirin', 'dose' => '100mg'],
        ]);

        $this->assertNotEmpty($alerts);
        $this->assertEquals('major', $alerts[0]['severity']);
    }

    public function test_drug_interaction_no_alerts_for_safe_pair(): void
    {
        $service = new DrugInteractionService();
        $alerts  = $service->checkInteractions([
            ['name' => 'Metformin', 'dose' => '500mg'],
            ['name' => 'Vitamin C', 'dose' => '500mg'],
        ]);
        $this->assertEmpty($alerts);
    }

    public function test_reconciliation_hard_stop_blocks_contraindicated_drug(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $service = new MedicationReconciliationService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('HARD_STOP_CONTRAINDICATION');

        $service->createReconciliation(
            patientId:   $patient->id,
            providerId:  $provider->id,
            facilityId:  $facility->id,
            medications: [
                ['name' => 'Methotrexate', 'dose' => '10mg', 'frequency' => 'weekly', 'source' => 'current'],
                ['name' => 'NSAIDs', 'dose' => '400mg', 'frequency' => 'OD', 'source' => 'new', 'flag_hard_stop' => true],
            ],
        );
    }
}
