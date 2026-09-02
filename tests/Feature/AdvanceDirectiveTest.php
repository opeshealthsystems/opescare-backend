<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Services\Clinical\AdvanceDirectiveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvanceDirectiveTest extends TestCase
{
    use RefreshDatabase;

    private AdvanceDirectiveService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AdvanceDirectiveService::class);
    }

    public function test_can_register_dnr_directive(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $directive = $this->service->register([
            'patient_id'     => $patient->id,
            'facility_id'    => $facility->id,
            'directive_type' => 'dnr',
            'effective_date' => Carbon::now()->toDateString(),
        ]);

        $this->assertTrue((bool) $directive->is_active);
        $this->assertEquals('dnr', $directive->directive_type);
    }

    public function test_has_active_dnr_returns_true_when_dnr_exists(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $this->service->register([
            'patient_id'     => $patient->id,
            'facility_id'    => $facility->id,
            'directive_type' => 'dnr',
            'effective_date' => Carbon::now()->toDateString(),
        ]);

        $this->assertTrue($this->service->hasActiveDnr($patient->id));
    }

    public function test_revoke_deactivates_directive(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();
        $user     = User::factory()->create();

        $directive = $this->service->register([
            'patient_id'     => $patient->id,
            'facility_id'    => $facility->id,
            'directive_type' => 'dnr',
            'effective_date' => Carbon::now()->toDateString(),
        ]);

        $this->assertTrue($this->service->hasActiveDnr($patient->id));

        $this->service->revoke($directive->id, $user->id);

        $this->assertFalse($this->service->hasActiveDnr($patient->id));
    }

    public function test_get_healthcare_proxy_returns_correct_directive(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $this->service->register([
            'patient_id'                    => $patient->id,
            'facility_id'                   => $facility->id,
            'directive_type'                => 'healthcare_proxy',
            'effective_date'                => Carbon::now()->toDateString(),
            'healthcare_proxy_name'         => 'Mary Doe',
            'healthcare_proxy_phone'        => '+237670000099',
            'healthcare_proxy_relationship' => 'spouse',
        ]);

        $found = $this->service->getHealthcareProxy($patient->id);

        $this->assertNotNull($found);
        $this->assertEquals('Mary Doe', $found->healthcare_proxy_name);
    }
}
