<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Zero-dependency RS256 JWT implementation using PHP's built-in openssl extension.
 *
 * Tokens are signed with a 2048-bit RSA private key at storage/keys/jwt_private.pem.
 * Verification uses the matching public key at storage/keys/jwt_public.pem.
 *
 * Security hardening (migration sprint):
 *   - `aud` claim added: tokens are bound to 'opescare-api'; cross-service reuse rejected.
 *   - JTI revocation: revokeToken() writes to revoked_tokens table + cache.
 *     verify() checks the cache first (fast path), then DB (slow path, cache miss).
 *   - TTL configurable via OPESCARE_JWT_TTL env var (default 3600 s).
 */
class JwtService
{
    private const ALGORITHM = 'RS256';
    private const ISSUER    = 'opescare-connect';
    private const AUDIENCE  = 'opescare-api';
    private const TTL       = 3600;

    /** Cache key prefix for revoked JTIs. */
    private const CACHE_PREFIX = 'jti_revoked:';

    /** Memoized public JWK (kty/use/alg/kid/n/e) — derived once per request. */
    private ?array $jwkCache = null;

    // ── Issue ─────────────────────────────────────────────────────────────────

    /**
     * Issue a signed RS256 JWT for an IntegrationClient.
     *
     * @param  array  $claims  Additional claims (client_id, scopes, facility_id, env)
     * @return string          Signed JWT string
     */
    public function issue(array $claims): string
    {
        $now = time();
        // Read via config (not env()) so the TTL survives config:cache in prod.
        $ttl = (int) config('services.opescare_jwt.ttl', self::TTL);

        $header = $this->base64UrlEncode(json_encode([
            'alg' => self::ALGORITHM,
            'typ' => 'JWT',
            'kid' => $this->keyId(),
        ]));

        $payload = $this->base64UrlEncode(json_encode(array_merge([
            'iss' => self::ISSUER,
            'aud' => self::AUDIENCE,
            'iat' => $now,
            'exp' => $now + $ttl,
            'jti' => (string) Str::uuid(),
        ], $claims)));

        $signingInput = $header . '.' . $payload;

        return $signingInput . '.' . $this->base64UrlEncode($this->sign($signingInput));
    }

    // ── Verify ────────────────────────────────────────────────────────────────

    /**
     * Verify and decode a JWT. Returns the payload claims array on success.
     *
     * Checks (in order):
     *   1. Structure (3-part)
     *   2. RS256 signature
     *   3. Expiry (exp)
     *   4. Issuer (iss)
     *   5. Audience (aud) — grace: tokens without aud are accepted (pre-migration)
     *   6. JTI revocation — cache first, then DB
     *
     * @throws \RuntimeException on any validation failure
     */
    public function verify(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new \RuntimeException('Invalid JWT structure.');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // Signature verification
        $signingInput = $headerB64 . '.' . $payloadB64;
        $signature    = $this->base64UrlDecode($signatureB64);

        if (openssl_verify($signingInput, $signature, $this->publicKey(), OPENSSL_ALGO_SHA256) !== 1) {
            throw new \RuntimeException('JWT signature verification failed.');
        }

        $payload = json_decode($this->base64UrlDecode($payloadB64), true);

        if (! is_array($payload)) {
            throw new \RuntimeException('JWT payload is not valid JSON.');
        }

        // Expiry
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new \RuntimeException('JWT has expired.');
        }

        // Issuer
        if (($payload['iss'] ?? null) !== self::ISSUER) {
            throw new \RuntimeException('JWT issuer mismatch.');
        }

        // Audience — grace period for tokens without aud (issued before migration)
        if (isset($payload['aud']) && $payload['aud'] !== self::AUDIENCE) {
            throw new \RuntimeException('JWT audience mismatch.');
        }

        // JTI revocation check
        $jti = $payload['jti'] ?? null;
        if ($jti && $this->isRevoked($jti)) {
            throw new \RuntimeException('JWT has been revoked.');
        }

        return $payload;
    }

    // ── Revocation ────────────────────────────────────────────────────────────

    /**
     * Revoke a JWT by its JTI claim.
     *
     * Writes to revoked_tokens table (persistent) AND sets a cache key (fast path).
     * The cache TTL matches the token's remaining lifetime so the entry auto-expires.
     *
     * @param  string       $jti        The jti claim from the JWT payload
     * @param  int          $exp        The exp claim (Unix timestamp) for TTL calculation
     * @param  string|null  $revokedBy  User/system that triggered the revocation
     * @param  string|null  $reason     Human-readable reason
     * @param  string|null  $clientId   integration_client_id for audit trail
     */
    public function revokeToken(
        string  $jti,
        int     $exp,
        ?string $revokedBy = null,
        ?string $reason    = null,
        ?string $clientId  = null,
    ): void {
        $expiresAt = \Carbon\Carbon::createFromTimestamp($exp);

        // Persist to DB — source of truth, survives cache flush
        DB::table('revoked_tokens')->upsert([
            'jti'        => $jti,
            'expires_at' => $expiresAt,
            'revoked_at' => now(),
            'revoked_by' => $revokedBy,
            'reason'     => $reason,
            'client_id'  => $clientId,
        ], uniqueBy: ['jti'], update: ['revoked_at', 'reason']);

        // Write to cache — fast path for subsequent requests
        $cacheTtl = max(0, $exp - time());
        if ($cacheTtl > 0) {
            Cache::put(self::CACHE_PREFIX . $jti, true, $cacheTtl);
        }

        Log::info('jwt_token_revoked', [
            'jti'        => $jti,
            'revoked_by' => $revokedBy,
            'reason'     => $reason,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
    }

    /**
     * Check whether a JTI has been revoked.
     *
     * Fast path: Laravel cache (Redis or database).
     * Slow path: revoked_tokens table (on cache miss); repopulates cache on hit.
     */
    public function isRevoked(string $jti): bool
    {
        // Fast path
        if (Cache::has(self::CACHE_PREFIX . $jti)) {
            return true;
        }

        // Slow path — DB lookup (cache miss after flush/restart)
        $row = DB::table('revoked_tokens')
            ->where('jti', $jti)
            ->where('expires_at', '>', now())
            ->first();

        if ($row) {
            // Repopulate cache so next request is fast
            $cacheTtl = max(0, strtotime($row->expires_at) - time());
            if ($cacheTtl > 0) {
                Cache::put(self::CACHE_PREFIX . $jti, true, $cacheTtl);
            }
            return true;
        }

        return false;
    }

    // ── Public key publication (JWKS / RFC 7517, RFC 7638) ─────────────────────

    /**
     * The active signing key as a JWK (RFC 7517). Public key material only —
     * the private key is never exposed. Memoized for the request.
     */
    public function publicJwk(): array
    {
        if ($this->jwkCache !== null) {
            return $this->jwkCache;
        }

        $details = openssl_pkey_get_details($this->publicKey());
        if ($details === false || ! isset($details['rsa']['n'], $details['rsa']['e'])) {
            throw new \RuntimeException('Unable to derive JWK from the JWT public key.');
        }

        $n = $this->base64UrlEncode($details['rsa']['n']);
        $e = $this->base64UrlEncode($details['rsa']['e']);

        // RFC 7638 thumbprint over the canonical {"e","kty","n"} member set —
        // a stable, deterministic key id that rotates only when the key rotates.
        $kid = $this->base64UrlEncode(hash('sha256', json_encode([
            'e'   => $e,
            'kty' => 'RSA',
            'n'   => $n,
        ], JSON_UNESCAPED_SLASHES), true));

        return $this->jwkCache = [
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => self::ALGORITHM,
            'kid' => $kid,
            'n'   => $n,
            'e'   => $e,
        ];
    }

    /** Stable key id (RFC 7638 thumbprint) embedded in each JWT header `kid`. */
    public function keyId(): string
    {
        return $this->publicJwk()['kid'];
    }

    /** The JWK Set (RFC 7517) served at /.well-known/jwks.json. */
    public function jwks(): array
    {
        return ['keys' => [$this->publicJwk()]];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function sign(string $data): string
    {
        $signature = '';
        if (! openssl_sign($data, $signature, $this->privateKey(), OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Failed to sign JWT: ' . openssl_error_string());
        }
        return $signature;
    }

    private function privateKey(): \OpenSSLAsymmetricKey
    {
        $path = storage_path('keys/jwt_private.pem');

        if (! file_exists($path)) {
            throw new \RuntimeException("JWT private key not found at {$path}.");
        }

        $key = openssl_pkey_get_private(file_get_contents($path));

        if ($key === false) {
            throw new \RuntimeException('Failed to load JWT private key: ' . openssl_error_string());
        }

        return $key;
    }

    private function publicKey(): \OpenSSLAsymmetricKey
    {
        $path = storage_path('keys/jwt_public.pem');

        if (! file_exists($path)) {
            throw new \RuntimeException("JWT public key not found at {$path}.");
        }

        $key = openssl_pkey_get_public(file_get_contents($path));

        if ($key === false) {
            throw new \RuntimeException('Failed to load JWT public key: ' . openssl_error_string());
        }

        return $key;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
