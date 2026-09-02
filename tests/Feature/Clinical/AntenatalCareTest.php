<?php
namespace Tests\Feature\Clinical;

use App\Models\AntenatalRecord;
use App\Models\AntenatalVisit;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Modules\Maternity\Services\AntenatalCareService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AntenatalCareTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_open_antenatal_record(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $service = new AntenatalCareService();
        $record  = $service->openRecord(
            patientId:  $patient->id,
            providerId: $provider->id,
            facilityId: $facility->id,
            lmpDate:    '2026-01-01',
            gravida:    2,
            para:       1,
        );

        $this->assertInstanceOf(AntenatalRecord::class, $record);
        $this->assertEquals($patient->id, $record->patient_id);
        $this->assertEquals(2, $record->gravida);
        $this->assertNotNull($record->estimated_delivery_date);
    }

    public function test_edd_calculated_from_lmp(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $service = new AntenatalCareService();
        $record  = $service->openRecord($patient->id, $provider->id, $facility->id, '2026-01-01', 1, 0);

        $expectedEdd = Carbon::parse('2026-01-01')->addDays(280)->toDateString();
        $this->assertEquals($expectedEdd, $record->estimated_delivery_date->toDateString());
    }

    public function test_can_record_antenatal_visit(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $service = new AntenatalCareService();
        $record  = $service->openRecord($patient->id, $provider->id, $facility->id, '2026-01-01', 1, 0);

        $visit = $service->recordVisit(
            recordId:       $record->id,
            providerId:     $provider->id,
            visitDate:      '2026-02-15',
            gestationalAge: 6,
            bloodPressure:  '110/70',
            fetalHeartRate: 148,
            notes:          'All normal',
        );

        $this->assertInstanceOf(AntenatalVisit::class, $visit);
        $this->assertEquals(6, $visit->gestational_age_weeks);
    }
}
