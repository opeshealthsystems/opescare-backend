<?php
namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Http\Middleware\VerifyPartnerTrustLevel;
use App\Modules\Partners\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PartnerTrustLevelTest extends TestCase
{
    use RefreshDatabase;
    public function test_partner_below_minimum_trust_level_is_rejected(): void
    {
        $middleware = new VerifyPartnerTrustLevel();

        // Create a partner with low trust level (1)
        $partner = Partner::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'partner_type' => 'healthcare_provider',
            'legal_name' => 'Test Partner Low Trust',
            'status' => 'active',
            'trust_level' => 'level_1_registered',
        ]);

        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Partner-ID', $partner->uuid);

        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;
            return response()->json(['ok' => true]);
        };

        // Require trust level 3, but partner is level 1
        $response = $middleware->handle($request, $next, '3');

        $this->assertFalse($called, 'Next should NOT be called for low-trust partner');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_partner_meeting_minimum_trust_level_is_allowed(): void
    {
        $middleware = new VerifyPartnerTrustLevel();

        // Create a partner with sufficient trust level (4)
        $partner = Partner::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'partner_type' => 'healthcare_provider',
            'legal_name' => 'Test Partner High Trust',
            'status' => 'active',
            'trust_level' => 'level_4_clinical_trusted',
        ]);

        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Partner-ID', $partner->uuid);

        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;
            return response()->json(['ok' => true]);
        };

        // Require trust level 3, partner is level 4 (sufficient)
        $response = $middleware->handle($request, $next, '3');

        $this->assertTrue($called, 'Next SHOULD be called for sufficient-trust partner');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_partner_with_exact_minimum_trust_level_is_allowed(): void
    {
        $middleware = new VerifyPartnerTrustLevel();

        // Create a partner with exactly the required trust level (3)
        $partner = Partner::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'partner_type' => 'healthcare_provider',
            'legal_name' => 'Test Partner Exact Trust',
            'status' => 'active',
            'trust_level' => 'level_3_operational_verified',
        ]);

        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Partner-ID', $partner->uuid);

        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;
            return response()->json(['ok' => true]);
        };

        // Require trust level 3, partner is exactly level 3
        $response = $middleware->handle($request, $next, '3');

        $this->assertTrue($called, 'Exact trust level match should pass');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_missing_min_trust_level_defaults_to_1(): void
    {
        $middleware = new VerifyPartnerTrustLevel();

        // Create a partner with trust level 0 (unverified)
        $partner = Partner::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'partner_type' => 'healthcare_provider',
            'legal_name' => 'Test Partner Zero Trust',
            'status' => 'active',
            'trust_level' => 'level_0_unverified',
        ]);

        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Partner-ID', $partner->uuid);

        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;
            return response()->json(['ok' => true]);
        };

        // When minTrustLevel is null/not passed, it should default to 1
        // Partner is level 0 (below the default minimum of 1)
        $response = $middleware->handle($request, $next, null);

        $this->assertFalse($called, 'Next should NOT be called for trust_level 0 when default minimum is 1');
        $this->assertEquals(403, $response->getStatusCode());
    }
}
