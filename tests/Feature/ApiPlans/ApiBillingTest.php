<?php

namespace Tests\Feature\ApiPlans;

use App\Http\Middleware\EnforceApiQuota;
use App\Models\ApiInvoice;
use App\Models\ApiPlan;
use App\Models\ApiUsageLog;
use App\Models\IntegrationClient;
use App\Services\ApiBilling\ApiBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiBillingTest extends TestCase
{
    use RefreshDatabase;

    private function client(string $id, string $plan, string $env = 'production'): IntegrationClient
    {
        return IntegrationClient::create([
            'client_id' => $id, 'client_secret' => 'x', 'status' => 'active',
            'environment' => $env, 'api_plan_key' => $plan, 'scopes' => [],
        ]);
    }

    private function pastDueInvoice(string $clientId, string $plan = 'growth'): ApiInvoice
    {
        return ApiInvoice::create([
            'client_id' => $clientId, 'plan_key' => $plan,
            'period_start' => now()->subMonth()->startOfMonth(),
            'period_end' => now()->subMonth()->endOfMonth(),
            'base_amount_xaf' => 75000, 'total_xaf' => 75000,
            'status' => 'issued', 'issued_at' => now()->subDays(30), 'due_at' => now()->subDays(16),
        ]);
    }

    public function test_generates_invoice_with_base_and_overage(): void
    {
        ApiPlan::create([
            'key' => 'mini', 'name' => 'Mini', 'rate_limit_per_min' => 10,
            'monthly_request_quota' => 2, 'price_xaf' => 1000, 'overage_price_xaf' => 10,
            'support_level' => 'community', 'features' => [], 'is_public' => false, 'sort' => 9,
        ]);
        $this->client('acme', 'mini');
        for ($i = 0; $i < 5; $i++) {
            ApiUsageLog::create(['integration_client_id' => 'acme', 'endpoint' => 'x', 'method' => 'GET', 'response_status' => 200, 'ip_address' => '41.0.0.1']);
        }

        $count = app(ApiBillingService::class)->generateForMonth(now());

        $this->assertSame(1, $count);
        $inv = ApiInvoice::where('client_id', 'acme')->first();
        $this->assertSame(5, $inv->used_requests);
        $this->assertSame(3, $inv->overage_requests);    // 5 used - 2 included
        $this->assertSame(1000, $inv->base_amount_xaf);
        $this->assertSame(30, $inv->overage_amount_xaf); // 3 * 10 XAF
        $this->assertSame(1030, $inv->total_xaf);
        $this->assertSame('issued', $inv->status);
    }

    public function test_free_plan_is_not_billed(): void
    {
        $this->client('free-1', 'sandbox', 'sandbox');
        ApiUsageLog::create(['integration_client_id' => 'free-1', 'endpoint' => 'x', 'method' => 'GET', 'response_status' => 200, 'ip_address' => '41.0.0.1']);

        app(ApiBillingService::class)->generateForMonth(now());

        $this->assertSame(0, ApiInvoice::where('client_id', 'free-1')->count());
    }

    public function test_generation_is_idempotent(): void
    {
        ApiPlan::create(['key' => 'mini', 'name' => 'Mini', 'rate_limit_per_min' => 10, 'monthly_request_quota' => 2, 'price_xaf' => 1000, 'overage_price_xaf' => 0, 'support_level' => 'community', 'features' => [], 'is_public' => false, 'sort' => 9]);
        $this->client('acme', 'mini');

        app(ApiBillingService::class)->generateForMonth(now());
        app(ApiBillingService::class)->generateForMonth(now());

        $this->assertSame(1, ApiInvoice::where('client_id', 'acme')->count());
    }

    public function test_mark_overdue_and_dunning_blocks_api(): void
    {
        $this->client('late-1', 'growth');
        $this->pastDueInvoice('late-1');

        $this->assertSame(1, app(ApiBillingService::class)->markOverdue());
        $this->assertTrue(ApiBillingService::clientIsPastDue('late-1'));

        $req = Request::create('/api/v1/connect/x', 'GET');
        $req->attributes->set('integration_client_id', 'late-1');
        $res = (new EnforceApiQuota())->handle($req, fn ($r) => response()->json(['ok' => true]));

        $this->assertSame(402, $res->getStatusCode());
        $this->assertSame('PAYMENT_REQUIRED', $res->getData()->error_code);
    }

    public function test_paid_invoice_clears_dunning(): void
    {
        $this->client('paid-1', 'growth');
        $this->pastDueInvoice('paid-1')->markPaid('mtn_momo', 'ref-123');

        app(ApiBillingService::class)->markOverdue();

        $this->assertFalse(ApiBillingService::clientIsPastDue('paid-1'));
    }
}
