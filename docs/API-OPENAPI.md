# OpenAPI Specification — Generated, Full-Coverage, Self-Syncing

**Status:** Full coverage (all endpoints)
**Spec:** [`public/openapi.json`](../public/openapi.json) (OpenAPI 3.1.0, generated)
**Rendered:** ReDoc at `/docs/playground`
**Generator:** `php artisan opescare:generate-openapi`
**Related:** [API-VERSIONING.md](API-VERSIONING.md), [API-RESOURCES.md](API-RESOURCES.md), [API-OAUTH.md](API-OAUTH.md)

---

## 1. How it works

The spec is **generated from the live route table** by
[`GenerateOpenApi`](../app/Console/Commands/GenerateOpenApi.php), not
hand-maintained. It currently documents **592 operations across 550 paths —
100% of the `api/*` and `.well-known/*` surface.**

For every route it emits: the real path + method, a tag (from the path prefix),
the security scheme (derived from the route's gathered middleware —
`VerifyIntegrationClient` → `ClientId`+`ClientSecret`, `auth.bearer` →
`BearerAuth`, `sdk.token` → `SdkToken`, `bridge.agent` → `BridgeAgentKey`,
`auth.mobile` → `MobileToken`, admin/session → `SessionAuth`, else public), path
parameters, a generic request body for writes, and the standard response set
(success + the shared `Error` envelope for 4xx/5xx).

Because paths come straight from the router, the spec **cannot drift or invent
an endpoint**. Regenerate after any route change:

```bash
php artisan opescare:generate-openapi   # writes public/openapi.json
```

## 2. Guardrail (bidirectional, self-syncing)

[`OpenApiContractTest`](../tests/Feature/Architecture/OpenApiContractTest.php)
enforces the spec is in sync with the routes **both ways**:

- **No phantom** — every documented operation resolves to a real route.
- **No gaps** — every real `api/`/`.well-known/` route is documented.

So adding or removing a route without regenerating **fails CI**, keeping
coverage at 100% permanently.

## 3. Curated rich operations (overrides)

Generic stubs are honest but coarse. High-value external operations carry full
request/response schemas via [`config/openapi.php`](../config/openapi.php):

- `schemas` — extra component schemas (the API resources `ClinicalNote`,
  `AllergyRecord`, `Diagnosis`, plus the OAuth `TokenResponse`,
  `IntrospectionResponse`, `Jwk`/`Jwks`, `OAuthServerMetadata`).
- `overrides` — full operation objects keyed `"METHOD /full/path"` that replace
  the generated stub. Currently curated: the OAuth token + introspection
  endpoints, the JWKS + metadata discovery docs, and the five clinical
  Encounter writes.

To enrich an endpoint: add an entry under `overrides` (and any new `schemas`),
then regenerate. The contract test still guarantees the path is real.

## 4. Backlog (enrichment, not coverage)

Coverage is complete; what remains is **richer per-endpoint detail**. Priority
order for adding overrides: Connect records → SDK → Mobile partner → FHIR. The
response schemas should reference the API resource shapes (see
[API-RESOURCES.md](API-RESOURCES.md)). Internal admin/staff endpoints can stay
as generated stubs.
