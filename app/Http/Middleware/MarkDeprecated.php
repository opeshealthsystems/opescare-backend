<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Emits RFC 8594 Deprecation + Sunset headers on a route or route group.
 *
 * Usage (per route):
 *   ->middleware('api.deprecated:2026-12-31')
 *   ->middleware('api.deprecated:2026-12-31,https://docs.opescare.com/changelog')
 *
 * No v1 routes are deprecated today — this is the mechanism that lets a v1
 * endpoint be retired safely once a v2 exists, giving partners machine-readable
 * notice ahead of the sunset date. See docs/API-VERSIONING.md.
 */
class MarkDeprecated
{
    public function handle(Request $request, Closure $next, ?string $sunset = null, ?string $docUrl = null): Response
    {
        $response = $next($request);

        // Deprecation: true => the endpoint is deprecated as of now.
        $response->headers->set('Deprecation', 'true');

        if ($sunset !== null) {
            try {
                // Normalise to UTC before formatting: RFC 7231's literal "GMT"
                // suffix must match the actual instant regardless of app.timezone
                // or whether the caller passed a time component.
                $response->headers->set(
                    'Sunset',
                    (new \DateTimeImmutable($sunset, new \DateTimeZone('UTC')))
                        ->setTimezone(new \DateTimeZone('UTC'))
                        ->format(\DateTimeInterface::RFC7231)
                );
            } catch (\Throwable) {
                // Malformed sunset date — skip the header rather than 500.
            }
        }

        $link = $docUrl ?: rtrim((string) config('app.url'), '/') . '/docs/changelog';
        $response->headers->set('Link', '<' . $link . '>; rel="deprecation"; type="text/html"');

        return $response;
    }
}
