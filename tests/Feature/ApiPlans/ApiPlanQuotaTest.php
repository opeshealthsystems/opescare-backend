<?php

namespace Tests\Feature\ApiPlans;

use App\Http\Middleware\EnforceApiQuota;
use App\Models\ApiPlan;
use App\Models\ApiUsageLog;
use App\Models\IntegrationClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiPlanQuotaTest extends TestCase
{
    use RefreshDatabase;

    private function invokeQuota(string $clientId): \Symfony\Component\HttpFoundation\Response
    {
        $req = Request::create('/api/v1/connect/test', 'GET');
        $req->attributes->set('integration_client_id', $clientId);
        return (new EnforceApiQuota())->handle($req, fn ($r) => response()->json(['ok' => true]));
    }

    public function test_reference_plans_are_seeded(): void
    {
        $this->assertSame(3, ApiPlan::count());
        $this->assertSame(500000, ApiPlan::forKey('growth')->monthly_request_quota);
        $this->assertTrue(ApiPlan::forKey('scale')->isUnlimited());
        $this->assertSame(75000, ApiPlan::forKey('growth')->price_xaf);
    }

    public function test_public_pricing_page_renders_plans(): void
    {
        $res = $this->get('/developers/pricing');

        $res->assertOk();
        $res->assertSee('Sandbox');
        $res->assertSee('Growth');
        $res->assertSee('Scale');
        $res->assertSee('FCFA');
        $res->assertSee('Free'); // sandbox price
    }

    public function test_test_client_is_never_metered(): void
    {
        $this->assertSame(200, $this->invokeQuota('test_client_id')->getStatusCode());
    }

    public function test_under_quota_passes_with_headers(): void
    {
        IntegrationClient::create([
            'client_id' => 'g-1', 'client_secret' => 'x', 'status' => 'active',
            'environment' => 'production', 'api_plan_key' => 'growth', 'scopes' => [],
        ]);

        $res = $this->invokeQuota('g-1');

        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('500000', $res->headers->get('X-Quota-Limit'));
        $this->assertSame('499999', $res->headers->get('X-Quota-Remaining'));
    }

    public function test_unlimited_plan_passes(): void
    {
        IntegrationClient::create([
            'client_id' => 's-1', 'client_secret' => 'x', 'status' => 'active',
            'environment' => 'production', 'api_plan_key' => 'scale', 'scopes' => [],
        ]);

        $this->assertSame(200, $this->invokeQuota('s-1')->getStatusCode());
    }

    public function test_over_quota_is_blocked_429(): void
    {
        ApiPlan::create([
            'key' => 'tiny', 'name' => 'Tiny', 'rate_limit_per_min' => 10,
            'monthly_request_quota' => 2, 'price_xaf' => 0, 'support_level' => 'community',
            'features' => [], 'is_public' => false, 'sort' => 9,
        ]);
        IntegrationClient::create([
            'client_id' => 'tiny-1', 'client_secret' => 'x', 'status' => 'active',
            'environment' => 'production', 'api_plan_key' => 'tiny', 'scopes' => [],
        ]);
        for ($i = 0; $i < 2; $i++) {
            ApiUsageLog::create([
                'integration_client_id' => 'tiny-1', 'endpoint' => 'api/v1/x',
                'method' => 'GET', 'response_status' => 200, 'ip_address' => '41.0.0.1',
            ]);
        }

        $res = $this->invokeQuota('tiny-1');

        $this->assertSame(429, $res->getStatusCode());
        $this->assertSame('QUOTA_EXCEEDED', $res->getData()->error_code);
        $this->assertNotEmpty($res->headers->get('Retry-After'));
    }
}
