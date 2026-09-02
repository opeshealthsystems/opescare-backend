<?php
namespace Tests\Feature\Tenancy;

use App\Models\TenantOnboardingCheckpoint;
use App\Services\Tenancy\TenantOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private TenantOnboardingService $service;
    private string $facilityId = 'facility-uuid-test-001';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TenantOnboardingService();
    }

    public function test_initialize_creates_default_steps(): void
    {
        $this->service->initializeOnboarding($this->facilityId);

        $count = TenantOnboardingCheckpoint::where('facility_id', $this->facilityId)->count();
        $this->assertEquals(7, $count);
    }

    public function test_initialize_includes_billing_step(): void
    {
        $this->service->initializeOnboarding($this->facilityId);

        $step = TenantOnboardingCheckpoint::where('facility_id', $this->facilityId)
            ->where('step_key', 'billing_configured')
            ->first();

        $this->assertNotNull($step);
        $this->assertTrue($step->required);
    }

    public function test_complete_step_marks_as_done(): void
    {
        $this->service->initializeOnboarding($this->facilityId);
        $this->service->completeStep($this->facilityId, 'facility_profile_complete');

        $step = TenantOnboardingCheckpoint::where('facility_id', $this->facilityId)
            ->where('step_key', 'facility_profile_complete')
            ->first();

        $this->assertTrue($step->completed);
        $this->assertNotNull($step->completed_at);
    }

    public function test_progress_returns_percent_complete(): void
    {
        $this->service->initializeOnboarding($this->facilityId);
        $this->service->completeStep($this->facilityId, 'facility_profile_complete');

        $progress = $this->service->getProgress($this->facilityId);

        $this->assertEquals(7, $progress['total_steps']);
        $this->assertEquals(1, $progress['completed_steps']);
        $this->assertGreaterThan(0, $progress['percent_complete']);
        $this->assertLessThan(100, $progress['percent_complete']);
        $this->assertFalse($progress['is_complete']);
    }

    public function test_is_complete_true_when_all_required_done(): void
    {
        $this->service->initializeOnboarding($this->facilityId);

        // Complete all required steps (first 4)
        foreach (['facility_profile_complete', 'staff_roles_configured', 'billing_configured', 'first_provider_added'] as $step) {
            $this->service->completeStep($this->facilityId, $step);
        }

        $progress = $this->service->getProgress($this->facilityId);
        $this->assertTrue($progress['is_complete']);
    }
}
