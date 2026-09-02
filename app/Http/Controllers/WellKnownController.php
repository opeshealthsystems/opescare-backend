<?php

namespace App\Http\Controllers;

use App\Services\JwtService;
use Illuminate\Http\JsonResponse;

/**
 * Publishes the OpesCare Connect OAuth 2.0 discovery documents at the domain
 * root (no auth, cacheable):
 *
 *   GET /.well-known/jwks.json                    — RFC 7517 JWK Set (public key)
 *   GET /.well-known/oauth-authorization-server   — RFC 8414 server metadata
 *
 * These let third-party systems verify Connect RS256 tokens without a shared
 * secret and discover the token/introspection endpoints programmatically.
 *
 * Documented deviations from strict RFC 8414 (see docs/API-OAUTH.md):
 *   - `issuer` is the API base URL; the JWT `iss` claim is the stable logical
 *     identifier "opescare-connect".
 *   - Client authentication to the token & introspection endpoints uses the
 *     platform's X-Client-ID / X-Client-Secret headers.
 */
class WellKnownController extends Controller
{
    public function __construct(private readonly JwtService $jwt) {}

    /** RFC 7517 JWK Set — the active RSA public signing key. */
    public function jwks(): JsonResponse
    {
        return response()->json($this->jwt->jwks())
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /** RFC 8414 OAuth 2.0 Authorization Server Metadata. */
    public function authorizationServerMetadata(): JsonResponse
    {
        $issuer = rtrim((string) (config('services.opescare_oauth.issuer') ?: config('app.url')), '/');

        return response()->json([
            'issuer'                                => $issuer,
            'token_endpoint'                        => $issuer . '/api/v1/connect/auth/token',
            'introspection_endpoint'                => $issuer . '/api/v1/connect/auth/introspect',
            'jwks_uri'                              => $issuer . '/.well-known/jwks.json',
            'grant_types_supported'                 => ['client_credentials'],
            'response_types_supported'              => [],
            'token_endpoint_auth_methods_supported' => ['client_secret_post'],
            'introspection_endpoint_auth_methods_supported' => ['opescare_client_headers'],
            'token_endpoint_auth_signing_alg_values_supported' => ['RS256'],
            'scopes_supported'                      => ['patients', 'subscriptions', 'system', 'labs', 'prescriptions'],
            'ui_locales_supported'                  => ['en', 'fr'],
            'service_documentation'                 => $issuer . '/docs',
        ])->header('Cache-Control', 'public, max-age=3600');
    }
}
