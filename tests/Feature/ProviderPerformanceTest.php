<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use App\Services\Reports\ProviderPerformanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private ProviderPerformanceService $service;
    private User $provider;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service  = app(ProviderPerformanceService::class);
        $this->provider = User::factory()->create();
        $this->facility = Facility::factory()->create();
    }

    public function test_performance_summary_returns_expected_keys(): void
    {
        $from = Carbon::now()->subMonth();
        $to   = Carbon::now();

        $summary = $this->service->getSummary($this->provider->id, $from, $to);

        $this->assertArrayHasKey('total_visits', $summary);
        $this->assertArrayHasKey('avg_visit_duration_minutes', $summary);
        $this->assertArrayHasKey('prescription_count', $summary);
        $this->assertArrayHasKey('lab_order_count', $summary);
        $this->assertArrayHasKey('referral_count', $summary);
        $this->assertArrayHasKey('referral_accepted_rate', $summary);
        $this->assertArrayHasKey('patient_return_rate', $summary);
    }

    public function test_facility_summary_returns_array_of_provider_summaries(): void
    {
        $from = Carbon::now()->subMonth();
        $to   = Carbon::now();

        $summary = $this->service->getFacilitySummary($this->facility->id, $from, $to);

        $this->assertIsArray($summary);
    }

    public function test_top_diagnoses_returns_array_with_name_and_count(): void
    {
        $diagnoses = $this->service->getTopDiagnoses($this->provider->id, 5);

        $this->assertIsArray($diagnoses);
        if (!empty($diagnoses)) {
            $this->assertArrayHasKey('diagnosis', $diagnoses[0]);
            $this->assertArrayHasKey('count', $diagnoses[0]);
        }
    }

    public function test_total_visits_is_zero_when_provider_has_no_visits(): void
    {
        $from    = Carbon::now()->subMonth();
        $to      = Carbon::now();
        $summary = $this->service->getSummary($this->provider->id, $from, $to);

        $this->assertEquals(0, $summary['total_visits']);
    }

    public function test_performance_endpoint_requires_authentication(): void
    {
        $response = $this->getJson("/api/v1/reports/providers/{$this->provider->id}/performance");
        $response->assertStatus(401);
    }
}
