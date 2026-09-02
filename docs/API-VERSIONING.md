# OpesCare API — Versioning & Backward-Compatibility Policy

This is the contract OpesCare makes to every integration partner (HIS, lab,
pharmacy, insurer, SDK, and Connect consumers) about how the API evolves.

## 1. Versioning scheme

The API is versioned in the **URL path**: every endpoint lives under a major
version prefix.

```
https://api.opescare.com/api/v1/...
https://api.opescare.com/api/fhir/R4/...
```

- **Major version** (`v1`, future `v2`) appears in the path. A new major version
  is created **only** for breaking changes (see §3).
- The FHIR surface is versioned by the FHIR release (`R4`) independently of the
  proprietary Connect API version.
- Every API response carries an **`X-API-Version`** header naming the served
  contract version. Webhook payloads carry a top-level **`schema_version`** and
  deliveries include an **`X-OpesCare-Webhook-Version`** header.

There is one major version today (`v1`). We do not use `Accept`-header version
negotiation; the version is always explicit in the path.

## 2. Backward-compatibility guarantee (additive-only within a major version)

Within a major version (e.g. `v1`), changes are **additive only**. We will not
make a breaking change to a `v1` endpoint without shipping it under `v2` and
following the deprecation process in §3.

**Non-breaking (allowed at any time, no notice required):**
- Adding a new endpoint, resource, or optional request field.
- Adding a new field to a response body.
- Adding a new optional query parameter.
- Adding a new value to an open-ended enum where the consumer is expected to
  tolerate unknown values (e.g. a new `status`).
- Adding a new webhook event type.
- Relaxing a validation rule, or making a required field optional.
- Performance, wording of human-readable `message` strings, and bug fixes that
  bring behaviour in line with the documented contract.

**Breaking (requires a new major version + deprecation of the old):**
- Removing or renaming an endpoint, field, or enum value.
- Changing a field's type or its semantic meaning.
- Making an optional request field required, or adding a new required field.
- Tightening a validation rule that previously-valid requests now fail.
- Changing authentication, scopes required, error codes, or the response
  envelope shape.
- Changing the meaning of an existing webhook event or its payload shape.

> **Consumers must** ignore unknown response fields, tolerate new enum values,
> and not depend on field ordering. Clients that do this absorb every
> non-breaking change automatically.

## 3. Deprecation process

When an endpoint or major version must be retired:

1. The successor (`v2` endpoint, or a replacement field) ships first.
2. The old endpoint is marked deprecated. It keeps working and starts returning
   **RFC 8594** headers (applied via the `api.deprecated` middleware):
   - `Deprecation: true`
   - `Sunset: <retirement date>` (HTTP-date)
   - `Link: <changelog-url>; rel="deprecation"; type="text/html"`
3. The deprecation and the sunset date are published in `/docs/changelog`.
4. **Minimum notice: 180 days** between deprecation and sunset for any endpoint
   that handles patient data. After the sunset date the endpoint may return
   `410 Gone`.
5. Partners on the **integration-certification** program (see the Developer
   Portal) are notified directly, and re-certification is required against the
   new version before the old one is removed.

## 4. The wire contract is decoupled from the database

API responses are serialised through an explicit resource/DTO layer
(`app/Http/Resources/*`), **not** by returning Eloquent models directly. This
means a database or model change cannot accidentally add, remove, or rename a
field on the wire — the resource is the contract. New model columns are only
exposed when the resource is deliberately updated (a non-breaking, additive
change per §2).

## 5. Safe rollout for partners

Breaking changes are gated behind the partner-onboarding pipeline:

- **Sandbox first.** Every developer app starts sandbox-only; production access
  is reviewed and granted per scope.
- **Integration certification.** Partners certify against a captured API version
  (test runs + badges). A new major version requires re-certification.
- **Idempotency.** Write endpoints accept an `Idempotency-Key` header so retries
  during a migration are safe.

## 6. Where to watch for changes

- `/docs/changelog` — human-readable change log and deprecation notices.
- `public/openapi.json` — the machine-readable OpenAPI 3.1 contract (generated, full-coverage; `php artisan opescare:generate-openapi`).
- `X-API-Version` response header and webhook `schema_version` — runtime signals.
