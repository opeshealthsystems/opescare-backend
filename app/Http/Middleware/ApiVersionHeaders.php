<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stamps every API response with the served contract version.
 *
 * Consumers read X-API-Version to detect which version of the OpesCare API
 * contract answered their request. The platform uses URL-path versioning
 * (/api/v1/...) with an additive-only backward-compatibility guarantee — see
 * docs/API-VERSIONING.md. Deprecation of a version is signalled separately via
 * the `api.deprecated` middleware (RFC 8594 Deprecation/Sunset headers).
 *
 * Registered on the whole `api` middleware group in bootstrap/app.php.
 */
class ApiVersionHeaders
{
    /** The current stable API contract version. */
    public const VERSION = 'v1';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-API-Version', self::VERSION);

        return $response;
    }
}
