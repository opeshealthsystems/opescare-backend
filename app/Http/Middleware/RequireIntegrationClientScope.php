<?php

namespace App\Http\Middleware;

use App\Models\IntegrationClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * integration.scope:<scope> — runs AFTER VerifyIntegrationClient and refuses a
 * header-authenticated client whose `scopes` column does not hold <scope>
 * (or '*'). Facility scoping is still VerifyIntegrationClient's job; this adds
 * "is this client allowed to do this kind of write at all".
 *
 * The testing-only bypass client (no IntegrationClient model in the request)
 * is not a real credential and is let through, as VerifyIntegrationClient does.
 */
class RequireIntegrationClientScope
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        $client = $request->attributes->get('integration_client');

        if (! $client instanceof IntegrationClient) {
            if (app()->environment('testing') && $request->attributes->get('integration_client_id') === 'test_client_id') {
                return $next($request);
            }

            return response()->json(['error' => 'invalid_client', 'message' => 'Integration client not resolved.'], 403);
        }

        $held = is_array($client->scopes) ? $client->scopes : (json_decode((string) $client->scopes, true) ?: []);

        if (! in_array('*', $held, true) && ! in_array($scope, $held, true)) {
            return response()->json([
                'error'          => 'insufficient_scope',
                'message'        => 'This integration client is not granted the required scope.',
                'required_scope' => $scope,
            ], 403);
        }

        return $next($request);
    }
}
