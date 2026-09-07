<?php

namespace Tests\Feature\Infrastructure;

use App\Http\Middleware\AddSecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * `'unsafe-eval'` is granted to Horizon's dashboard and to nothing else.
 *
 * Horizon's compiled Vue bundle builds its render functions at runtime, so
 * without it the queue dashboard rendered as a blank page. The grant is worth
 * pinning in a test in both directions: the page has to work, and the
 * exception must not quietly spread to the rest of the site.
 */
class ContentSecurityPolicyTest extends TestCase
{
    private function cspFor(string $path): string
    {
        $middleware = new AddSecurityHeaders();

        $response = $middleware->handle(
            Request::create($path, 'GET'),
            function () {
                $response = new Response('<html></html>', 200);
                $response->headers->set('Content-Type', 'text/html; charset=UTF-8');

                return $response;
            }
        );

        return (string) $response->headers->get('Content-Security-Policy');
    }

    public function test_horizon_may_evaluate_its_own_bundle(): void
    {
        $this->assertStringContainsString("'unsafe-eval'", $this->cspFor('/' . config('horizon.path', 'horizon')));
        $this->assertStringContainsString("'unsafe-eval'", $this->cspFor('/' . config('horizon.path', 'horizon') . '/dashboard'));
    }

    public function test_nothing_else_may(): void
    {
        foreach (['/', '/login', '/admin/plans', '/docs', '/patient/dashboard', '/verify/qr'] as $path) {
            $this->assertStringNotContainsString(
                "'unsafe-eval'",
                $this->cspFor($path),
                $path . ' was granted unsafe-eval'
            );
        }
    }
}
