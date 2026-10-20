<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 301 www.<host> → the canonical host from config('app.url').
 *
 * www.opescare.com used to serve a full 200 duplicate of the site that
 * canonicalised to itself (SEO audit 2026-10-20, F1). Only fires when the
 * request host starts with "www." AND the configured app.url host does not,
 * so a deployment that deliberately lives on www is never redirected away.
 * Path and query string are preserved.
 */
class RedirectWwwToApex
{
    public function handle(Request $request, Closure $next): Response
    {
        $host    = strtolower($request->getHost());
        $appUrl  = (string) config('app.url');
        $appHost = strtolower((string) parse_url($appUrl, PHP_URL_HOST));

        if ($appHost !== '' && str_starts_with($host, 'www.') && ! str_starts_with($appHost, 'www.')
            && substr($host, 4) === $appHost) {
            $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';
            $port   = parse_url($appUrl, PHP_URL_PORT);

            return redirect()->away(
                $scheme . '://' . $appHost . ($port ? ':' . $port : '') . $request->getRequestUri(),
                301
            );
        }

        return $next($request);
    }
}
