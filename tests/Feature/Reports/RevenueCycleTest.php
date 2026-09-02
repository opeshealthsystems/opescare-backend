<?php

namespace Tests\Feature\Reports;

use App\Models\Facility;
use App\Models\InsuranceClaim;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueCycleTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->facility = Facility::factory()->create();
        $this->headers  = [
            'X-Client-ID'     => 'test_client_id',
            'X-Client-Secret' => 'test_client_secret',
        ];
    }

    public function test_summary_returns_all_expected_keys(): void
    {
        InsuranceClaim::factory()->count(3)->create([
            'facility_id'     => $this->facility->id,
            'claimed_amount'  => 1000.00,
            'approved_amount' => 900.00,
            'paid_amount'     => 900.00,
            'status'          => 'paid',
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/reports/revenue-cycle/summary?facility_id=' . $this->facility->id
                . '&from=' . Carbon::now()->subMonth()->format('Y-m-d')
                . '&to='   . Carbon::now()->format('Y-m-d'));

        $response->assertOk();

        $data = $response->json('data');
        $expectedKeys = [
            'total_billed', 'total_collected', 'collection_rate',
            'total_pending', 'total_denied', 'denial_rate',
            'avg_days_to_payment', 'outstanding_ar', 'claims_by_status',
        ];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $data, "Missing key: {$key}");
        }
    }

    public function test_collection_rate_calculation_is_correct(): void
    {
        InsuranceClaim::factory()->count(3)->create([
            'facility_id'    => $this->facility->id,
            'claimed_amount' => 1000.00,
            'paid_amount'    => 900.00,
            'status'         => 'paid',
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/reports/revenue-cycle/summary?facility_id=' . $this->facility->id);

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals(3000.00, $data['total_billed']);
        $this->assertEquals(2700.00, $data['total_collected']);
        $this->assertEquals(90.00,   $data['collection_rate']);
    }

    public function test_aging_report_buckets_claims_by_age(): void
    {
        InsuranceClaim::factory()->create([
            'facility_id'    => $this->facility->id,
            'claimed_amount' => 800.00,
            'paid_amount'    => 0.00,
            'status'         => 'submitted',
            'created_at'     => Carbon::now()->subDays(45),
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/reports/revenue-cycle/aging?facility_id=' . $this->facility->id);

        $response->assertOk();

        $data = $response->json('data');
        $this->assertArrayHasKey('31-60', $data);
        $this->assertEquals(1,      $data['31-60']['count']);
        $this->assertEquals(800.00, $data['31-60']['amount']);
    }

    public function test_monthly_trend_returns_correct_structure(): void
    {
        InsuranceClaim::factory()->create([
            'facility_id'    => $this->facility->id,
            'claimed_amount' => 1200.00,
            'paid_amount'    => 1200.00,
            'status'         => 'paid',
            'created_at'     => Carbon::now(),
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/reports/revenue-cycle/trend?facility_id=' . $this->facility->id . '&months=3');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertIsArray($data);

        if (count($data) > 0) {
            $this->assertArrayHasKey('month',     $data[0]);
            $this->assertArrayHasKey('billed',    $data[0]);
            $this->assertArrayHasKey('collected', $data[0]);
            $this->assertArrayHasKey('denied',    $data[0]);
        }
    }

    public function test_summary_without_auth_returns_401(): void
    {
        $response = $this->getJson('/api/v1/reports/revenue-cycle/summary?facility_id=' . $this->facility->id);
        $response->assertStatus(401);
    }
}
