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

    /**
     * script-src carries a per-request nonce and never 'unsafe-inline'. The
     * nonce is also exposed to the view layer (`@cspNonce`) so the inline
     * blocks that remain in the Blade views are the only inline script that runs.
     */
    public function test_scripts_are_nonced_not_unsafe_inline(): void
    {
        $middleware = new AddSecurityHeaders();
        $request = Request::create('/patient/dashboard', 'GET');

        $response = $middleware->handle($request, function () {
            $response = new Response('<html></html>', 200);
            $response->headers->set('Content-Type', 'text/html; charset=UTF-8');

            return $response;
        });

        $csp   = (string) $response->headers->get('Content-Security-Policy');
        $nonce = (string) $request->attributes->get('csp_nonce');

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/]{22}==$/', $nonce, 'nonce is 16 random bytes, base64');
        $this->assertMatchesRegularExpression("/script-src [^;]*'nonce-" . preg_quote($nonce, '/') . "'/", $csp);
        $this->assertDoesNotMatchRegularExpression("/script-src [^;]*'unsafe-inline'/", $csp, "script-src must not allow 'unsafe-inline'");
    }

    public function test_every_request_gets_a_fresh_nonce(): void
    {
        $nonces = [];
        foreach ([1, 2, 3] as $i) {
            $request = Request::create('/', 'GET');
            (new AddSecurityHeaders())->handle($request, fn () => new Response('', 200));
            $nonces[] = $request->attributes->get('csp_nonce');
        }

        $this->assertCount(3, array_unique($nonces));
    }

    public function test_no_view_keeps_an_inline_event_handler(): void
    {
        // Inline on*= handlers cannot carry a nonce; every one of them was moved to
        // the data-* attributes dispatched by public/js/op-actions.js. A new one
        // would silently do nothing in the browser, so fail the build instead.
        $offenders = [];
        foreach (\Illuminate\Support\Facades\File::allFiles(resource_path('views')) as $file) {
            if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'emails' . DIRECTORY_SEPARATOR)) {
                continue;
            }
            if (preg_match_all('/\s(on(?:click|change|submit|input|load|keyup|keydown|mouseover|focus|blur))=/i', $file->getContents(), $m)) {
                $offenders[] = $file->getRelativePathname() . ' (' . implode(', ', array_unique($m[1])) . ')';
            }
        }

        $this->assertSame([], $offenders, "Inline event handlers found:\n" . implode("\n", $offenders));
    }

    public function test_no_view_has_an_unnonced_inline_script(): void
    {
        $offenders = [];
        foreach (\Illuminate\Support\Facades\File::allFiles(resource_path('views')) as $file) {
            $path = str_replace('\\', '/', $file->getRelativePathname());
            if (str_starts_with($path, 'emails/') || str_starts_with($path, 'pdf/') || str_starts_with($path, 'vendor/')) {
                continue;
            }
            if (preg_match_all('/<script(?![^>]*\bsrc=)(?![^>]*@cspNonce)(?![^>]*type="application\/(?:ld\+)?json")[^>]*>/', $file->getContents(), $m)) {
                $offenders[] = $path . ': ' . $m[0][0];
            }
        }

        $this->assertSame([], $offenders, "Inline <script> without @cspNonce:\n" . implode("\n", $offenders));
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
