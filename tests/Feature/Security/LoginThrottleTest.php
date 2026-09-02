<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Disable ONLY CSRF so requests reach the route's throttle:5,1 middleware.
        $this->withoutMiddleware(VerifyCsrfToken::class);
        RateLimiter::clear(sha1('brute@opescare.test|127.0.0.1'));
    }

    public function test_web_login_is_rate_limited(): void
    {
        $statuses = [];
        for ($i = 0; $i < 8; $i++) {
            $statuses[] = $this->post('/login', [
                'email' => 'brute@opescare.test',
                'password' => 'wrong-'.$i,
            ])->getStatusCode();
        }

        // After 5 attempts/min the throttle must return 429 Too Many Requests.
        $this->assertContains(429, $statuses, 'Web login should be throttled (429) after repeated attempts.');
    }
}
