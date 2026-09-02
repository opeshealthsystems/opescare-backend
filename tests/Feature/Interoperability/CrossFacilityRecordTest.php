<?php
namespace Tests\Feature\Interoperability;

use App\Models\CrossFacilityRecordRequest;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Services\Interoperability\CrossFacilityRecordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossFacilityRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_request_record_transfer(): void
    {
        $patient        = Patient::factory()->create();
        $requestingFac  = Facility::factory()->create();
        $sourceFac      = Facility::factory()->create();
        $requestingUser = User::factory()->create();

        $service = new CrossFacilityRecordService();
        $request = $service->requestRecords(
            patientId:          $patient->id,
            requestingFacility: $requestingFac->id,
            sourceFacility:     $sourceFac->id,
            requestedBy:        $requestingUser->id,
            purpose:            'Referral continuity',
            recordTypes:        ['lab_results', 'vital_signs'],
        );

        $this->assertInstanceOf(CrossFacilityRecordRequest::class, $request);
        $this->assertEquals('pending', $request->status);
        $this->assertContains('lab_results', $request->record_types);
    }

    public function test_request_requires_patient_consent(): void
    {
        $patient        = Patient::factory()->create();
        $requestingFac  = Facility::factory()->create();
        $sourceFac      = Facility::factory()->create();
        $requestingUser = User::factory()->create();

        $service = new CrossFacilityRecordService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('PATIENT_CONSENT_REQUIRED');

        $service->requestRecords(
            patientId:          $patient->id,
            requestingFacility: $requestingFac->id,
            sourceFacility:     $sourceFac->id,
            requestedBy:        $requestingUser->id,
            purpose:            'Marketing',
            recordTypes:        ['clinical_notes'],
            requireConsent:     true,
            hasConsent:         false,
        );
    }

    public function test_can_approve_record_request(): void
    {
        $patient        = Patient::factory()->create();
        $requestingFac  = Facility::factory()->create();
        $sourceFac      = Facility::factory()->create();
        $requestingUser = User::factory()->create();
        $approver       = User::factory()->create();

        $service = new CrossFacilityRecordService();
        $request = $service->requestRecords(
            $patient->id, $requestingFac->id, $sourceFac->id,
            $requestingUser->id, 'Referral', ['lab_results']
        );

        $approved = $service->approveRequest($request->id, $approver->id);
        $this->assertEquals('approved', $approved->status);
        $this->assertNotNull($approved->approved_at);
    }
}
