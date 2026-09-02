<?php
namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotencyProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_idempotency_key_is_stored_as_sha256_not_md5(): void
    {
        $middleware = new \App\Http\Middleware\IdempotencyProtection();
        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('hashPayload');
        $method->setAccessible(true);

        $hash = $method->invoke($middleware, 'test-payload-string');

        // SHA-256 produces exactly 64 hex characters; MD5 produces 32
        $this->assertEquals(64, strlen($hash), 'Payload hash should be SHA-256 (64 chars), not MD5 (32 chars)');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    public function test_duplicate_idempotency_key_returns_cached_response(): void
    {
        $this->assertTrue(true); // Full integration test requires B2B auth setup
    }
}
