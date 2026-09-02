<?php
namespace App\Http\Middleware;

use App\Models\ApiUsageLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiUsage
{
    public function handle(Request $request, Closure $next): Response
    {
        $start    = microtime(true);
        $response = $next($request);
        $ms       = (int) round((microtime(true) - $start) * 1000);

        $clientId = $request->header('X-Integration-Client-Id');
        if ($clientId) {
            // Sanitize: truncate and strip non-printable chars to prevent log poisoning
            $clientId   = substr(preg_replace('/[^\x20-\x7E]/', '', $clientId), 0, 100);
            $facilityId = $request->header('X-Facility-Id');
            $facilityId = $facilityId ? substr(preg_replace('/[^\x20-\x7E]/', '', $facilityId), 0, 36) : null;

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
