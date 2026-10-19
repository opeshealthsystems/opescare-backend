<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

/**
 * The 'api' limiter was defined but attached to nothing, so every /api/* route
 * without its own throttle (finder search, directory, chart reads) was
 * unlimited. throttleApi() now puts a per-caller ceiling on the whole group.
 */
class ApiBaselineThrottleTest extends TestCase
{
    public function test_anonymous_api_calls_carry_the_ip_ceiling(): void
    {
        $this->getJson('/api/mobile/app-config')
            ->assertHeader('X-RateLimit-Limit', '300');
    }

    public function test_bearer_callers_are_bucketed_per_token_not_per_ip(): void
    {
        // Even a token the auth layer will refuse is limited on its own bucket,
        // so carrier-NAT neighbours never share one.
        $a = $this->withHeader('Authorization', 'Bearer token-a')->getJson('/api/mobile/me');
        $a->assertHeader('X-RateLimit-Limit', '600');
        $this->assertSame('599', $a->headers->get('X-RateLimit-Remaining'));

        $b = $this->withHeader('Authorization', 'Bearer token-b')->getJson('/api/mobile/me');
        $this->assertSame('599', $b->headers->get('X-RateLimit-Remaining'));
    }

    public function test_the_ceiling_is_enforced(): void
    {
        for ($i = 0; $i < 300; $i++) {
            $this->getJson('/api/mobile/app-config');
        }

        $this->getJson('/api/mobile/app-config')->assertStatus(429);
    }
}
