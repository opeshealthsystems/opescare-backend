<?php
namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\PostnatalVisit;
use App\Models\PregnancyRecord;
use App\Models\User;
use App\Modules\Maternity\Services\MaternityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaternityTest extends TestCase
{
    use RefreshDatabase;

    private MaternityService $service;
    private User $provider;
    private Patient $patient;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service  = app(MaternityService::class);
        $this->provider = User::factory()->create();
        $this->patient  = Patient::factory()->create();
        $this->facility = Facility::factory()->create();
    }

    /**
     * Regression: MaternityController::recordPostnatalVisit() called
     * MaternityService::recordPostnatalVisit(), which did not exist, so the
     * endpoint threw BadMethodCallException on every request. Nothing covered
     * it, so a full green suite sat on top of a dead endpoint.
     */
    public function test_can_record_a_postnatal_visit(): void
    {
        $visit = $this->service->recordPostnatalVisit([
            'patient_id'           => $this->patient->id,
            'facility_id'          => $this->facility->id,
            'provider_id'          => $this->provider->id,
            'visit_date'           => now()->subDays(3)->toDateString(),
            'days_postpartum'      => 3,
            'bp_systolic'          => 118,
            'bp_diastolic'         => 76,
            'weight_kg'            => 68.40,
            'lochia'               => 'rubra',
            'wound_healing'        => 'normal',
            'breastfeeding_status' => 'exclusive',
            'infant_weight_grams'  => 3200,
            'notes'                => 'Mother and infant well. No pyrexia.',
            'next_visit_plan'      => 'Review at day 7.',
        ]);

        $this->assertInstanceOf(PostnatalVisit::class, $visit);
        $this->assertDatabaseHas('postnatal_visits', [
            'id'              => $visit->id,
            'patient_id'      => $this->patient->id,
            'facility_id'     => $this->facility->id,
            'days_postpartum' => 3,
            'lochia'          => 'rubra',
        ]);
        $this->assertSame(3200, $visit->infant_weight_grams);
        $this->assertSame('exclusive', $visit->breastfeeding_status);
    }

    /**
     * Postnatal care continues after a pregnancy record is closed, so a visit
     * must not require one — the controller keys on patient_id, not pregnancy.
     */
    public function test_postnatal_visit_does_not_require_a_pregnancy_record(): void
    {
        $visit = $this->service->recordPostnatalVisit([
            'patient_id'      => $this->patient->id,
            'facility_id'     => $this->facility->id,
            'provider_id'     => $this->provider->id,
            'visit_date'      => now()->toDateString(),
            'days_postpartum' => 42,
        ]);

        $this->assertDatabaseHas('postnatal_visits', ['id' => $visit->id]);
        $this->assertNull($visit->lochia);
    }

    public function test_can_register_pregnancy(): void
    {
        $record = $this->service->registerPregnancy([
            'patient_id'       => $this->patient->id,
            'facility_id'      => $this->facility->id,
            'provider_id'      => $this->provider->id,
            'gravida'          => 2,
            'para'             => 1,
            'lmp'              => '2026-01-01',
            'edd'              => '2026-10-08',
            'pregnancy_status' => 'active',
            'blood_type'       => 'O',
            'rhesus_factor'    => 'positive',
            'high_risk'        => false,
            'risk_factors'     => [],
            'registered_at'    => now()->toDateTimeString(),
        ]);

        $this->assertInstanceOf(PregnancyRecord::class, $record);
        $this->assertNotNull($record->id);
        $this->assertEquals(2, $record->gravida);
        $this->assertEquals('active', $record->pregnancy_status);
        $this->assertDatabaseHas('pregnancy_records', ['id' => $record->id]);
    }

    public function test_can_record_antenatal_visit(): void
    {
        $record = $this->service->registerPregnancy([
            'patient_id'       => $this->patient->id,
            'facility_id'      => $this->facility->id,
            'provider_id'      => $this->provider->id,
            'gravida'          => 1,
            'para'             => 0,
            'lmp'              => '2026-01-01',
            'edd'              => '2026-10-08',
            'pregnancy_status' => 'active',
            'blood_type'       => 'A',
            'rhesus_factor'    => 'negative',
            'high_risk'        => false,
            'risk_factors'     => [],
            'registered_at'    => now()->toDateTimeString(),
        ]);

        $visit = $this->service->recordAntenatalVisit($record->id, [
            'patient_id'            => $this->patient->id,
            'facility_id'           => $this->facility->id,
            'provider_id'           => $this->provider->id,
            'visit_date'            => '2026-02-15',
            'gestational_age_weeks' => 6,
            'gestational_age_days'  => 3,
            'fundal_height_cm'      => 8.5,
            'fetal_heart_rate'      => 148,
            'presentation'          => 'cephalic',
            'weight_kg'             => 62.5,
            'bp_systolic'           => 110,
            'bp_diastolic'          => 70,
            'urine_protein'         => 'negative',
            'urine_glucose'         => 'negative',
            'oedema'                => 'none',
        ]);

        $this->assertInstanceOf(\App\Models\AntenatalVisit::class, $visit);
        $this->assertEquals($record->id, $visit->pregnancy_record_id);
        $this->assertEquals(6, $visit->gestational_age_weeks);
        $this->assertDatabaseHas('antenatal_visits', ['id' => $visit->id]);
    }

    public function test_can_record_delivery(): void
    {
        $record = PregnancyRecord::factory()->create([
            'patient_id'  => $this->patient->id,
            'facility_id' => $this->facility->id,
            'provider_id' => $this->provider->id,
        ]);

        $delivery = $this->service->recordDelivery($record->id, [
            'patient_id'         => $this->patient->id,
            'facility_id'        => $this->facility->id,
            'provider_id'        => $this->provider->id,
            'delivery_date'      => '2026-09-30',
            'delivery_mode'      => 'svd',
            'birth_weight_grams' => 3250,
            'apgar_1min'         => 8,
            'apgar_5min'         => 9,
            'neonatal_outcome'   => 'live',
        ]);

        $this->assertInstanceOf(\App\Models\DeliveryRecord::class, $delivery);
        $this->assertEquals('svd', $delivery->delivery_mode);
        $this->assertEquals(3250, $delivery->birth_weight_grams);
        $this->assertDatabaseHas('delivery_records', ['id' => $delivery->id]);
    }

    public function test_gestational_age_calculation(): void
    {
        $lmp    = Carbon::now()->subWeeks(10)->subDays(3)->toDateString();
        $result = $this->service->calculateGestationalAge($lmp);

        $this->assertArrayHasKey('weeks', $result);
        $this->assertArrayHasKey('days', $result);
        $this->assertEquals(10, $result['weeks']);
        $this->assertEquals(3, $result['days']);
    }

    public function test_antenatal_schedule_returns_recommended_weeks(): void
    {
        $record   = PregnancyRecord::factory()->create(['patient_id' => $this->patient->id]);
        $schedule = $this->service->getAntenatalSchedule($record->id);

        $expected = [4, 8, 12, 16, 20, 24, 28, 30, 32, 34, 36, 38, 40];
        $this->assertEquals($expected, $schedule);
    }

    public function test_high_risk_detection_on_elevated_bp(): void
    {
        $record = PregnancyRecord::factory()->create([
            'patient_id'   => $this->patient->id,
            'high_risk'    => false,
            'risk_factors' => [],
        ]);

        $this->service->recordAntenatalVisit($record->id, [
            'patient_id'            => $this->patient->id,
            'facility_id'           => $this->facility->id,
            'provider_id'           => $this->provider->id,
            'visit_date'            => now()->toDateString(),
            'gestational_age_weeks' => 28,
            'gestational_age_days'  => 0,
            'fundal_height_cm'      => 28.0,
            'weight_kg'             => 72.0,
            'bp_systolic'           => 145,
            'bp_diastolic'          => 95,
        ]);

        $this->assertTrue($this->service->isHighRisk($record->fresh()));
    }
}
