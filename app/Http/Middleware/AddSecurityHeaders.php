<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AddSecurityHeaders
 *
 * Applied globally to every response. Audit sprint hardening:
 *
 * ISO 27001 A.14.1 / OWASP A05:
 *   - HSTS added (max-age=31536000; includeSubDomains) — required for all HTTPS
 *     deployments. Forces browsers to HTTPS-only; prevents SSL stripping.
 *
 * OWASP A05 / CSP:
 *   - script-src has NO 'unsafe-inline': inline <script> blocks carry a
 *     per-request nonce (@cspNonce) and inline on*= handlers were replaced by
 *     data-* attributes (public/js/op-actions.js). Done 2026-10-12.
 *
 * OWASP A05 / Deprecated:
 *   - Removed X-XSS-Protection (deprecated in all modern browsers; its
 *     mode=block can actually introduce XSS vulnerabilities in IE/Edge ≤18).
 *
 * ISO 27799 §8.2 / PHI Cache:
 *   - Added Cache-Control: no-store for JSON/FHIR API responses. Prevents
 *     sensitive patient data from being cached by proxies or browser history.
 */
class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // One nonce per request. Every inline <script> in the views carries it via
        // @cspNonce (AppServiceProvider), so script-src needs no 'unsafe-inline':
        // injected markup cannot know the nonce, so injected script never runs.
        // Inline on*= handlers cannot be nonced at all; they were replaced by the
        // data-* attributes handled in public/js/op-actions.js.
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);
        \Illuminate\Support\Facades\View::share('cspNonce', $nonce);

        $response = $next($request);

        // The embeddable map page (/embed/*) is loaded in an iframe by the web
        // app and a WebView by the native app: it must be same-origin frameable
        // and allowed to use geolocation (for the "locate me"/directions map).
        $isEmbed = $request->is('embed/*');

        // The API reference pages render with Redoc, which builds its search
        // index inside a Web Worker created from a blob: URL. `worker-src`
        // falls back to `script-src`, which lists no blob:, so the worker was
        // blocked and the docs search box silently returned nothing. Granted
        // on these pages only, and only for workers — script-src is untouched.
        $isDocs = $request->is('docs') || $request->is('docs/*');

        // Laravel Horizon's dashboard is a compiled Vue bundle that builds its
        // render functions at runtime, which needs 'unsafe-eval'. Without it
        // the page loaded and then rendered nothing at all — a blank queue
        // dashboard, with the reason only visible in the browser console.
        //
        // Granted on Horizon's own path and nowhere else. It is reachable only
        // by a platform administrator, it serves Horizon's own asset rather
        // than anything a user can influence, and script-src stays as it is for
        // every other page on the deployment.
        $horizonPath = trim((string) config('horizon.path', 'horizon'), '/');
        $isHorizon = $horizonPath !== '' && ($request->is($horizonPath) || $request->is($horizonPath . '/*'));

        // ── Universal headers ────────────────────────────────────────────────
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', $isEmbed ? 'SAMEORIGIN' : 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // The sign-in page may use the camera, same-origin only, for the
        // OPESCARD scanner; every other page keeps it switched off.
        $camera = $request->is('login') ? 'camera=(self)' : 'camera=()';
        $response->headers->set('Permissions-Policy', $isEmbed ? "{$camera}, microphone=(), geolocation=(self)" : "{$camera}, microphone=(), geolocation=()");

        // HSTS — ISO 27001 A.14.1 / OWASP A05 (was missing)
        // Only meaningful over HTTPS; harmless over HTTP in development.
        $response->headers->set(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains'
        );

        // Remove server fingerprinting headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');
        // X-XSS-Protection intentionally NOT set — it is deprecated and
        // can introduce XSS vulnerabilities in IE/Edge ≤18.

        $contentType = $response->headers->get('Content-Type', '');

        // ── HTML responses: strict CSP ────────────────────────────────────────
        if (str_contains($contentType, 'text/html')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; " .
                // Scripts: self-hosted files plus inline blocks carrying this request's
                // nonce. No 'unsafe-inline' (OWASP A05). Horizon's compiled Vue bundle
                // additionally needs 'unsafe-eval', on its own path only (see above).
                // cdn.jsdelivr.net removed (security scan 2026-09-15, item 6): allowing
                // the whole CDN let any npm/GitHub file be loaded as script.
                "script-src 'self' 'nonce-{$nonce}'" . ($isHorizon ? " 'unsafe-eval'" : '') . "; " .
                // Styles still allow inline: ~1,000 style= attributes plus Leaflet and
                // Chart.js set element styles at runtime. Inline CSS cannot run code;
                // tracked as a lower-priority clean-up in docs/SECURITY_REMEDIATION.md.
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
                "font-src 'self' https://fonts.gstatic.com; " .
                "img-src 'self' data: https:; " .
                "connect-src 'self'; " .
                ($isDocs ? "worker-src 'self' blob:; " : '') .
                "frame-ancestors " . ($isEmbed ? "'self'" : "'none'") . ";"
            );
        }

        // ── JSON / FHIR API responses: prevent PHI caching ───────────────────
        // ISO 27799 §8.2: patient data must not be stored in caches or history.
        // Applies to all JSON API responses — Connect API, FHIR, patient portal.
        if (
            str_contains($contentType, 'application/json') ||
            str_contains($contentType, 'application/fhir+json')
        ) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        // ── Authenticated HTML/PDF: PHI must not persist in browser or proxy caches
        // ASVS V8.2.1. A signed-in page (chart, desk, passport) is never cacheable;
        // public finder pages keep their own caching headers.
        if (
            $request->user() !== null &&
            (str_contains($contentType, 'text/html') || str_contains($contentType, 'application/pdf'))
        ) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
