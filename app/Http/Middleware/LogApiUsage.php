<?php
namespace App\Http\Middleware;

use App\Models\ApiUsageLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Append one api_usage_logs row per attributed API request.
 *
 * Attribution comes ONLY from the authenticated bearer/credential context that
 * the auth middleware (VerifyBearerToken / VerifyIntegrationClient) stores in
 * $request->attributes['integration_client_id'] — the integration_clients.client_id
 * string. A caller-supplied X-Integration-Client-Id header used to be trusted
 * here, which let any caller charge its traffic to any client_id it named
 * (docs/DEVELOPER_DASHBOARD_AUDIT.md D-3). Headers are never read any more.
 *
 * This middleware is global and wraps the route stack, so the attributes are
 * populated by the time $next() returns. An unauthenticated request (no token,
 * refused token) has no client to attribute to and is not logged.
 */
class LogApiUsage
{
    public function handle(Request $request, Closure $next): Response
    {
        $start    = microtime(true);
        $response = $next($request);
        $ms       = (int) round((microtime(true) - $start) * 1000);

        $clientId = $request->attributes->get('integration_client_id');
        $clientId = is_string($clientId) && $clientId !== '' ? substr($clientId, 0, 100) : null;

        if ($clientId !== null) {
            $facilityId = $request->attributes->get('facility_id');
            $facilityId = is_string($facilityId) && $facilityId !== '' ? substr($facilityId, 0, 36) : null;

            try {
                ApiUsageLog::create([
                    'integration_client_id' => $clientId,
                    'endpoint'              => $request->path(),
                    'method'                => $request->method(),
                    'response_status'       => $response->getStatusCode(),
                    'response_time_ms'      => $ms,
                    'ip_address'            => $request->ip(),
                    'facility_id'           => $facilityId,
                    // logged_at set by DB default (useCurrent)
                ]);
            } catch (\Throwable $e) {
                // Never let analytics logging break the request
            }
        }

        return $response;
    }
}
