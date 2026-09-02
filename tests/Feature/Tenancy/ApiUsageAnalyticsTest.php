<?php
namespace Tests\Feature\Tenancy;

use App\Models\ApiUsageLog;
use App\Services\Tenancy\ApiUsageAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiUsageAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_usage_log_can_be_created(): void
    {
        $log = ApiUsageLog::create([
            'integration_client_id' => 'CLIENT-001',
            'endpoint'              => 'api/v1/patients',
            'method'                => 'GET',
            'response_status'       => 200,
            'response_time_ms'      => 45,
            'ip_address'            => '41.202.32.1',
        ]);

        $this->assertEquals('CLIENT-001', $log->integration_client_id);
        $this->assertEquals(200, $log->response_status);
    }

    public function test_api_usage_log_is_append_only(): void
    {
        $this->expectException(\LogicException::class);

        $log = ApiUsageLog::create([
            'integration_client_id' => 'CLIENT-001',
            'endpoint'              => 'api/v1/patients',
            'method'                => 'GET',
            'response_status'       => 200,
            'ip_address'            => '41.0.0.1',
        ]);

        $log->update(['response_status' => 500]);
    }

    public function test_analytics_aggregates_by_client(): void
    {
        for ($i = 0; $i < 5; $i++) {
            ApiUsageLog::create([
                'integration_client_id' => 'CLIENT-A',
                'endpoint'              => 'api/v1/patients',
                'method'                => 'GET',
                'response_status'       => 200,
                'response_time_ms'      => 50,
                'ip_address'            => '41.0.0.1',
            ]);
        }

        ApiUsageLog::create([
            'integration_client_id' => 'CLIENT-B',
            'endpoint'              => 'api/v1/appointments',
            'method'                => 'POST',
            'response_status'       => 201,
            'response_time_ms'      => 120,
            'ip_address'            => '41.0.0.2',
        ]);

        $service = new ApiUsageAnalyticsService();
        // logged_at defaults to the DB CURRENT_TIMESTAMP (UTC), which can land on
        // a different calendar date than the app clock near midnight. Use a window
        // wide enough that the just-inserted rows can never fall outside it,
        // regardless of the UTC/local boundary.
        $summary = $service->getSummaryForPeriod(
            now()->subDays(2)->toDateString(),
            now()->addDay()->toDateString()
        );

        $clientA = collect($summary)->firstWhere('integration_client_id', 'CLIENT-A');
        $this->assertNotNull($clientA);
        $this->assertEquals(5, $clientA['request_count']);
    }
}
