<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Enforces which route prefixes are accessible per subdomain.
 *
 * This middleware is OPTIONAL and OFF by default (the platform runs correctly
 * on a single domain without it). Enable it in production once subdomains are
 * configured by setting SUBDOMAIN_ROUTING=true in .env.
 *
 * Subdomain → allowed route prefix map mirrors the deployment documentation.
 * If a request arrives on a restricted subdomain for a route that does not
 * belong to it, a 404 is returned — the route effectively does not exist
 * from that subdomain's perspective.
 */
class EnforceSubdomainScope
{
    /**
     * Subdomain keyword → allowed URI prefixes.
     * The keyword is matched against the leftmost label of the Host header.
     */
    private const SCOPE = [
        'api'           => ['v1/', 'health'],
        'connect'       => ['v1/connect', 'v1/connect'],
        'fhir'          => ['fhir/'],
        'mobile-api'    => ['mobile/', 'provider-mobile/'],
        'lite'          => ['portals/lite', 'api/v1/lite', 'v1/lite'],
        'developer'     => ['portals/developer', 'signup/developer'],
        'docs'          => ['docs/'],
        'caremap'       => ['care-map', 'v1/care-map'],
        'bridge'        => ['v1/bridge'],
        'public-health' => ['v1/public-health'],
        'ussd'          => ['ussd/'],
        'status'        => ['status'],
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        // Only enforce when explicitly enabled
        if (!config('opescare.subdomain_routing', false)) {
            return $next($request);
        }

        $host      = $request->getHost();                          // e.g. api.opescare.com
        $parts     = explode('.', $host);
        $subdomain = count($parts) >= 3 ? $parts[0] : null;      // 'api'

        // No subdomain (bare domain) — let everything through
        if (!$subdomain || !isset(self::SCOPE[$subdomain])) {
            return $next($request);
        }

        $path    = ltrim($request->path(), '/');
        $allowed = self::SCOPE[$subdomain];

        foreach ($allowed as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $next($request);
            }
        }

        // Route does not belong to this subdomain
        abort(404);
    }
}
