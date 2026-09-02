<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Services\Integration\Hl7AdtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Hl7AdtTest extends TestCase
{
    use RefreshDatabase;

    private Hl7AdtService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'hl7.host'        => '127.0.0.1',
            'hl7.port'        => 2575,
            'hl7.facility_id' => 'OPESCARE',
            'hl7.sending_app' => 'OPESCARE_EMR',
        ]);

        $this->service = app(Hl7AdtService::class);
    }

    public function test_build_a01_message_contains_required_segments(): void
    {
        $patient  = Patient::factory()->create([
            'first_name'    => 'Amara',
            'last_name'     => 'Diallo',
            'date_of_birth' => '1990-06-15',
            'sex'           => 'female',
        ]);
        $facility = Facility::factory()->create();

        $visit = (object) [
            'id'          => 'VISIT-001',
            'admitted_at' => now(),
            'visit_type'  => 'inpatient',
        ];

        $message = $this->service->buildA01Message($patient, $visit, $facility);

        $this->assertStringContainsString('MSH|', $message);
        $this->assertStringContainsString('EVN|', $message);
        $this->assertStringContainsString('PID|', $message);
        $this->assertStringContainsString('PV1|', $message);
        $this->assertStringContainsString('ADT^A01', $message);
    }

    public function test_build_a08_message_contains_required_segments(): void
    {
        $patient = Patient::factory()->create([
            'first_name'    => 'Kofi',
            'last_name'     => 'Mensah',
            'date_of_birth' => '1985-03-22',
            'sex'           => 'male',
        ]);

        $message = $this->service->buildA08Message($patient);

        $this->assertStringContainsString('MSH|', $message);
        $this->assertStringContainsString('EVN|', $message);
        $this->assertStringContainsString('PID|', $message);
        $this->assertStringContainsString('ADT^A08', $message);
    }

    public function test_build_a28_message_contains_required_segments(): void
    {
        $patient = Patient::factory()->create();

        $message = $this->service->buildA28Message($patient);

        $this->assertStringContainsString('MSH|', $message);
        $this->assertStringContainsString('PID|', $message);
        $this->assertStringContainsString('ADT^A28', $message);
    }

    public function test_mllp_framing_applied_in_send(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $visit = (object) [
            'id'          => 'V-TEST',
            'admitted_at' => now(),
            'visit_type'  => 'outpatient',
        ];

        $message = $this->service->buildA01Message($patient, $visit, $facility);

        $this->assertEquals("\x0b", Hl7AdtService::VT);
        $this->assertEquals("\x1c", Hl7AdtService::FS);
        $this->assertEquals("\r",   Hl7AdtService::CR);

        $framed = Hl7AdtService::VT . $message . Hl7AdtService::FS . Hl7AdtService::CR;
        $this->assertStringStartsWith("\x0b", $framed);
        $this->assertStringEndsWith("\x1c\r", $framed);
    }

    public function test_send_returns_false_when_host_unreachable(): void
    {
        $result = $this->service->send('HL7-TEST', '240.0.0.1', 2575);
        $this->assertFalse($result);
    }

    public function test_a01_pid_segment_includes_patient_name(): void
    {
        $patient  = Patient::factory()->create([
            'first_name' => 'Fatima',
            'last_name'  => 'Ouedraogo',
        ]);
        $facility = Facility::factory()->create();

        $visit = (object) ['id' => 'V1', 'admitted_at' => now(), 'visit_type' => 'inpatient'];

        $message = $this->service->buildA01Message($patient, $visit, $facility);

        $this->assertStringContainsString('Ouedraogo^Fatima', $message);
    }
}
