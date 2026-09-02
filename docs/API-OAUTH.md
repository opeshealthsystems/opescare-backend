# OAuth 2.0 Conformance — Discovery, JWKS & Introspection

**Status:** Shipped (gap 4 of 4)
**Related:** [API-VERSIONING.md](API-VERSIONING.md), [API-RESOURCES.md](API-RESOURCES.md)

OpesCare Connect issues **RS256-signed JWT** access tokens via the OAuth 2.0
`client_credentials` grant. This document covers the conformance surface that
lets third-party systems verify those tokens and discover the server
programmatically.

## 1. Endpoints

| Endpoint | Spec | Auth | Purpose |
|----------|------|------|---------|
| `GET /.well-known/jwks.json` | RFC 7517 / 7518 | public | RSA public signing key (JWK Set) |
| `GET /.well-known/oauth-authorization-server` | RFC 8414 | public | Authorization-server metadata |
| `POST /api/v1/connect/auth/token` | RFC 6749 §4.4 | `client_secret_post` | Issue an access token |
| `POST /api/v1/connect/auth/introspect` | RFC 7662 | `X-Client-ID`/`X-Client-Secret` | Check a token's validity |

## 2. Verifying a token via JWKS (recommended)

The token is a standard three-segment RS256 JWT. Its header carries a `kid`
matching the key published at `/.well-known/jwks.json`, so any standard JWT
library can verify the signature **offline** without calling OpesCare:

```
1. GET /.well-known/jwks.json   → cache the JWK (Cache-Control: max-age=3600)
2. Decode the token header, read `kid`, select the matching JWK.
3. Verify the RS256 signature against that key.
4. Check claims: exp (not expired), iss == "opescare-connect", aud == "opescare-api".
```

The `kid` is the RFC 7638 thumbprint of the public key — stable across
deployments, and it changes only if the signing key is rotated.

> **Note on `aud`:** the server's own verification is lenient for backward
> compatibility — a token with **no** `aud` claim is accepted (a pre-migration
> grace period), while every newly issued token carries `aud == "opescare-api"`.
> New clients should still validate `aud` strictly.

## 3. Introspection (RFC 7662)

For callers that prefer the server to validate (e.g. to also catch
**revoked** tokens, which offline verification cannot):

```http
POST /api/v1/connect/auth/introspect
X-Client-ID: <your client id>
X-Client-Secret: <your client secret>
Content-Type: application/json

{ "token": "eyJhbGciOiJSUzI1NiIs..." }
```

Active token →

```json
{
  "active": true,
  "token_type": "Bearer",
  "scope": "patients labs",
  "client_id": "acme-hospital",
  "sub": "acme-hospital",
  "aud": "opescare-api",
  "iss": "opescare-connect",
  "exp": 1750700000,
  "iat": 1750696400,
  "jti": "…",
  "facility_id": "…",
  "environment": "production"
}
```

Inactive (malformed, bad signature, expired, **or revoked**) →

```json
{ "active": false }
```

Per RFC 7662 §2.2 the endpoint never reveals *why* a token is inactive, and per
§2.1 the introspection endpoint is itself authenticated (missing credentials →
`401`).

## 4. Authorization-server metadata

`GET /.well-known/oauth-authorization-server` returns the RFC 8414 document:
`issuer`, `token_endpoint`, `introspection_endpoint`, `jwks_uri`,
`grant_types_supported: ["client_credentials"]`, `scopes_supported`,
`token_endpoint_auth_signing_alg_values_supported: ["RS256"]`, and
`ui_locales_supported: ["en","fr"]`.

`issuer` is derived from `OPESCARE_OAUTH_ISSUER` (falling back to `APP_URL`).

> **Deploy prerequisite.** `OPESCARE_OAUTH_ISSUER` is unset by default, so the
> issuer falls back to `APP_URL` — which is `http://localhost` in local/dev. You
> **must** set `OPESCARE_OAUTH_ISSUER=https://api.opescare.com` (your public API
> host) in staging/production, or the discovery document will advertise
> localhost/non-HTTPS endpoints to partners. `ProductionSafetyServiceProvider`
> logs a `critical` warning when a production boot resolves the issuer to a
> localhost / non-HTTPS value.

## 5. Documented deviations (honest scope)

This is a *conformance foundation*, not a full RFC 8414 / OIDC provider. Known,
deliberate deviations:

1. **Issuer string vs URL.** The metadata `issuer` is the API base URL, while
   the JWT `iss` claim is the stable logical identifier `"opescare-connect"`.
   Aligning them is a future token-claim migration (additive, governed by the
   versioning policy) — clients should validate `iss == "opescare-connect"`.
2. **Client auth scheme.** The token endpoint reads credentials from the POST
   body (`client_secret_post`); the introspection endpoint uses the platform's
   `X-Client-ID` / `X-Client-Secret` headers (advertised as
   `opescare_client_headers`), consistent with all other OpesCare B2B APIs —
   not HTTP Basic.
3. **No authorization endpoint.** Only `client_credentials` is supported
   (server-to-server). There is no `authorization_endpoint`, so
   `response_types_supported` is empty. Interactive
   `authorization_code` + PKCE / SMART-on-FHIR is out of scope until third-party
   apps need to act on a *patient's* behalf.
4. **Two scope vocabularies.** `scopes_supported` advertises the coarse OAuth
   token scopes (`patients`, `subscriptions`, `system`, `labs`, `prescriptions`).
   Per-record authorization uses a separate, finer **consent-grant** model
   (`patients:read`, `patients:write`, `labs:write`, …) enforced by the
   `consent.grant:*` middleware. The two are intentionally distinct; a token's
   actual scopes come from its `IntegrationClient` registration.

## 6. Key rotation

Rotating the keypair at `storage/keys/jwt_{private,public}.pem` changes the
derived `kid` automatically; JWKS then advertises the new key. For zero-downtime
rotation, the JWK Set would need to publish both the old and new keys during an
overlap window — a follow-up when rotation is first exercised.

## 7. Tests

[`tests/Feature/Oauth/OAuthDiscoveryTest.php`](../tests/Feature/Oauth/OAuthDiscoveryTest.php)
covers JWKS shape + private-key-leak guard, metadata endpoints, `kid`↔JWKS
match, introspection active/inactive paths, and the endpoint-is-protected
requirement.
