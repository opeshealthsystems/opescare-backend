# Developer (integration partner) dashboard — production-readiness audit (2026-09-14)

Scope: the portal an integration partner logs into (`/developer`, roles
`developer`, `api_technical`, `sandbox_developer`, `partner_technical`,
`public_health_api` → sidebar `developer.blade.php`; `webhook_manager` →
`webhook_manager.blade.php`; `developer_org_admin`, `api_partner` →
`api_partner_admin.blade.php`; all listed in
`EnsurePortalAccess::PORTAL_ROLES['developer']`, lines 181–189). Every claim
below was checked against the code AND against finder.opescare.cloud logged in
as `demo.developer@opescare.test` over curl (login POST + cookie jar, GET only).
Model for this document: `docs/PHARMACY_DASHBOARD_AUDIT.md`.

## Verdict

**The developer portal cannot be opened at all on prod.** `/developer` and
`/developer/onboard` answer **500**, and every other developer page **302s back
to `/developer`** (no `developer_accounts` row exists for the demo user on
prod, so each controller action bounces to the dashboard, which then 500s).
A partner who signs in sees an error page and nothing else.

Behind that wall the credential flow (create app → client_id + secret shown
once → rotate → request production → admin approval) is real and correctly
scoped to the developer's own apps. Everything a partner would do NEXT is
missing: there is no way to register a webhook endpoint, no view of what the
partner's FHIR pushes did (`fhir_ingest_events` is written and read by nobody
in the UI), no quickstart with the partner's own credentials, and the analytics
page reads a table (`api_usage_metrics` via `api_credentials`) that nothing on
this platform populates for portal-created clients.

Prod status codes (2026-09-14, demo developer session):

| Path | Prod | Why |
|---|---|---|
| `/developer` | **500** | `partials/sidebars/developer.blade.php:19` calls `route('portals.developer.webhook_deliveries')` with no `clientId`; the route (`routes/web.php:732`) requires one → `UrlGenerationException` on every page that renders the sidebar. Reproduced locally (`Missing required parameter for [Route: portals.developer.webhook_deliveries]`). `webhook_manager.blade.php:19` and `api_partner_admin.blade.php` carry the same link. |
| `/developer/onboard` | **500** | same sidebar |
| `/developer/apps`, `/apps/create`, `/production-requests`, `/production-requests/create`, `/analytics` | 302 → `/developer` | `currentDeveloper()` (controller lines 40–48) finds no `developer_accounts` row for the demo user on prod — `DemoDeveloperAccountSeeder` is in `DemoDatabaseSeeder`, not in `FinderDemoSeeder` (which only creates the `users` row, line 81) |
| `/developer/webhook-deliveries` | 404 | there is no such route; only `/developer/apps/{clientId}/webhook-deliveries` |
| `/docs`, `/docs/authentication`, `/openapi.json`, `/opescare.postman_collection.json` | 200 | public docs are fine |

`EveryRoleReachesItsOwnDashboardTest::assertReaches` only asserts "not 302 and
not 403" (lines 58–66), so a 500 passes it — which is how this shipped.

## What works (code-verified; not reachable on prod until the 500 is fixed)

| Feature | Where | Notes |
|---|---|---|
| Own-apps scoping | `DeveloperPortalController` 149, 217–219, 270–272, 305–307, 332–334, 425–427 | every client lookup is `where('created_by', $developer->email)->firstOrFail()` → foreign ids 404 ✔ |
| Create app | `storeApp` 170–212 | `sandbox_<24>` client_id, `sk_sandbox_<48>` secret; Argon2id in `client_secret_argon` + legacy SHA-256 kept in sync; secret flashed once ✔; `facility_id` NULL |
| Show secret once / rotate | `apps.blade.php:176–185`, `app_detail.blade.php:270`, `rotateSecret` 295–320 | masked afterwards (`lbl_secret_masked`) ✔ |
| Enable / disable | `toggleApp` 323–340 | |
| Production request → admin approval | 362–450 + `adminApproveProductionRequest` 456–484 | promotes `environment` to production and rewrites `scopes` from `approved_scopes`. **Bug:** line 474 uses `$request->input('approved_scopes')` — if the admin form omits it, `json_encode(null)` writes the string `null` into `scopes`; line 464 defaulted `$approvedScopes` correctly but that variable is not used for the client update. |
| Webhook delivery log per app | `webhookDeliveries` 421–439 | paginated, scoped ✔ (but see D-2: nothing ever creates a subscription, so it is always empty) |
| Billing: plan, monthly usage, invoices, MoMo/OM payment | `showApp` 245–252, `payInvoice` 263–292 | |

## Gaps, ranked

### D-1 — Every developer page 500s (sidebar links to a parameterised route without the parameter)
Evidence above. Fix: a real `/developer/webhooks` overview route and all three
developer-tier sidebars (`developer`, `webhook_manager`, `api_partner_admin`)
pointed at it. The `api_partner_admin` link was a one-line change outside the
two sidebars this pass owns; without it `developer_org_admin` and
`api_partner` would have kept 500ing.

### D-2 — No way to register a webhook endpoint, anywhere
`app_detail.blade.php:465` says "Use the API to create subscriptions". There is
no such API: `grep WebhookSubscription::create` matches nothing in `app/`
(`ConnectAdminService` only pauses them, line 60; the platform's Connect admin
portal that used to create them was removed). `webhook_subscriptions` needs
`client_id`, `callback_url`, `webhook_secret` (plaintext — `SendWebhookJob:50`
HMAC-signs with it), `subscribed_events` (jsonb). Documented event types
(`docs/webhooks.blade.php:42`): `consent.granted`, `lab_result.released`,
`prescription.issued`. Also: `WebhookService::dispatch` has **no caller** in
`app/` except `Simulators/SimulatedWebhookService` — the three documented
events never fire in this app. A partner can be given a working registration
UI today; delivery will start only when the domain writes call `dispatch()`.

### D-3 — A partner cannot see what its FHIR pushes did
`fhir_ingest_events` is written on every accepted/refused write
(`FhirIngestGuard::record` 268–285) with `refusal_code`, `refusal_detail`,
`http_status`, hashed Health ID. No controller, view or route reads it. The
two refusals the door exists to make visible (`unknown_patient`,
`consent_required`) are invisible to the partner that triggered them.
`api_usage_logs` (`LogApiUsage`, global middleware `bootstrap/app.php:46`) is
equally unread by the portal — and it keys on a **caller-supplied**
`X-Integration-Client-Id` header (line 17), not on the bearer token, so
attribution is whatever the caller claims.

### D-4 — No quickstart with the partner's own credentials
`/docs/authentication` shows `"client_id": "YOUR_CLIENT_ID"` (line 39). The
dashboard has no link to `/docs`, `/openapi.json`, the Postman collection or
`/docs/sdk` (grep of `portals/developer/*.blade.php` for `docs` = 0 hits), and
no curl the partner can paste. Token URL is `POST /api/v1/connect/auth/token`
(`routes/api.php:36`, `grant_type=client_credentials`, JSON body).

### D-5 — Sandbox apps can never make a FHIR write (facility-less tokens)
`storeApp` leaves `facility_id` NULL; the token carries `facility_id: null`
(`AuthController:93`); `FhirIngestGuard::facilityId` (54–67) refuses with
`no_facility_in_token` (OperationOutcome). Consent is per
`(patient, facility)` (`requireConsent` 196–215), so a facility-less client
has no consent basis either. Production approval (lines 471–476) promotes the
environment but **does not attach a facility**. The FHIR partner layer is
usable only by facility-bound clients created outside this portal
(`IntegrationClientFactory` / seeders). The portal must say so.

### D-6 — Scopes are not the FHIR SMART scopes
`storeApp:199` hard-codes `['health_id:read','consents:read','patients:read']`;
the production-request list (`createProductionRequest` 373–394) offers 20
`resource:verb` scopes. The FHIR routes require
`system/Observation.write`, `system/DiagnosticReport.write`,
`system/Immunization.write` (`FhirIngestController` 79–81). None of the SMART
scopes is offerable or granted anywhere in the portal → even a facility-bound,
production-approved app from this portal gets `insufficient_scope` (403) on
every FHIR route.

### D-7 — Analytics is a placeholder
`analytics()` 573–612 reads `api_usage_metrics` joined through
`api_credentials.client_id`. Nothing creates `api_credentials` rows for
portal-created clients (`storeApp` writes only `integration_clients`), and
`ApiUsageAnalyticsService` (the only writer of `api_usage_metrics`) has no
caller. Dashboard/app-detail usage (`ApiUsageSnapshot::summaryForClient`) reads
`api_usage_snapshots`, also written by nothing. Both render "0 / No usage
recorded" forever. The only tables that ARE written are `api_usage_logs` and
`fhir_ingest_events`.

### D-8 — Onboarding is not self-serve
`/signup/developer` → `submitDeveloperRegister` (`PublicPageController`
631–678) creates a **`leads` row only** (`source = developer_signup`). No
`users` row, no e-mail verification, no sandbox client. An admin must create
the user; the first `/developer` visit then renders the onboarding form
(`dashboard()` 106–112) which creates the `developer_accounts` row with
`email_verified_at = now()` and `sandbox_only = true`. There is no approval
step for the account itself; only for production access.

### D-9 — Consent: portal has no view of grants; only the API refusal
No route or view touches `ConsentGrant` in the developer portal. Grants are
facility-scoped, not client-scoped, so a facility-less sandbox client has
nothing to show. Deliberately not built in this pass (see below).

### D-10 — Admin side is URL-only
`portals.admin.developer.production_requests` / `.accounts` (`routes/web.php`
740–744) appear in no admin sidebar (grep `portals.admin.developer` in
`partials/sidebars/` = 0). An approval an admin cannot navigate to.

### D-11 — Small things
* `dashboard.blade.php:147–148` — the "Webhooks" stat card links to My Apps.
* `webhook_deliveries.blade.php`, `app_detail.blade.php:391` print raw
  `$wh->status` / scope strings (not `@enum`); minor.
* `developer.blade.php` fallbacks (`?: 'Dashboard'`) are hard-coded English
  but every key exists in `lang/en|fr/public.php` (`i18n-audit` clean: 13 403 keys 1:1).

## Built in this pass (routes in `routes/developer_ext.php`, same middleware as `routes/web.php:712`)

| Gap | Route | Name | Roles |
|---|---|---|---|
| D-1, D-2 | `GET /developer/webhooks`, `POST /developer/webhooks`, `POST /developer/webhooks/{subscription}/toggle` | `portals.developer.webhooks[.store|.toggle]` | all `PORTAL_ROLES['developer']` |
| D-3 | `GET /developer/activity[?app=<own uuid>]` | `portals.developer.activity` | same |
| D-4, D-5, D-6 | `GET /developer/quickstart[?app=<own uuid>]` | `portals.developer.quickstart` | same |

Sidebars: `developer.blade.php` and `webhook_manager.blade.php` now link
Webhooks → `portals.developer.webhooks` and gain Activity (+ Quickstart on the
developer sidebar); `api_partner_admin.blade.php` only had its Webhooks link
repointed.

## Pass 2 (2026-09-14) — closed: D-3 attribution, D-5, D-7, D-9, D-10, webhook dispatch + replay

Tests: `tests/Feature/Developer/DeveloperCompletionPass2Test.php`.

### D-5 resolution — a production app is always facility-bound
Decision: **an app acts for exactly one facility, and that facility is attached
by the platform admin at production approval.** Consent on this platform is a
grant from a patient to a *facility* (`consent_grants.facility_id`), never to a
client, and `FhirIngestGuard::facilityId()` reads the facility from the token —
so the only way a partner app can hold a consent basis or make a FHIR write is
to carry a facility in its token. That facility is `integration_clients.facility_id`,
which `AuthController::issueToken` copies into the JWT claim.

* **Sandbox apps stay facility-less.** `storeApp` still writes `facility_id = NULL`;
  their tokens carry `facility_id: null`; every FHIR write is refused
  `no_facility_in_token` and no consent can be scoped to them. They can only read
  sandbox fixtures. The quickstart and the consents page say so.
* **Approval attaches the facility.** `adminApproveProductionRequest` now requires
  `facility_name` — the *exact* name of one row in `facilities` (no search, no
  partial match; zero or several matches are refused with a flash) — and writes
  `facility_id` on the client row alongside `environment = production`. Webhook
  subscriptions the app registered while facility-less get the same
  `facility_id`, so facility-scoped events reach them.
* **`approved_scopes` null-write fixed.** The handler used to read
  `approved_scopes` a second time for the client update and wrote
  `json_encode(null)` (the string `null`) into `scopes` whenever the form omitted
  it. It now resolves the list once — the admin's list if given, otherwise the
  requested scopes — and writes that to both the request and the client. The
  admin modal posts the requested scopes as hidden fields.

### D-3 — attribution at the source
`LogApiUsage` no longer reads `X-Integration-Client-Id` / `X-Facility-Id`. It
takes `integration_client_id` and `facility_id` from `$request->attributes`, which
only `VerifyBearerToken` (JWT claims) and `VerifyIntegrationClient` (client-secret
auth) populate. The middleware is global and logs after `$next()`, so the route
middleware has already set them. Unauthenticated requests are not logged (there is
no client to charge them to). A spoofed header is pinned as ignored by test.

### D-7 — analytics on the one table that is written
`DeveloperPortalController@analytics` reads `api_usage_logs` only, for the
developer's own `integration_clients.client_id` values, last 30 days: requests
per day (bar chart, red share = 4xx/5xx), request count, error rate, p50/p95 of
`response_time_ms` (`percentile_cont`), top endpoints, per-app counts, `?app=`
filter (foreign id → 404). Empty state reads "No calls yet." `api_usage_metrics`,
`api_credentials` and `api_usage_snapshots` are no longer read; the tables are
left in place (no migration).

### Webhooks — dispatch wired, replay added
`app/Modules/Connect/Services/WebhookEventPublisher.php` is the single caller of
`WebhookService::dispatch`. Every publisher runs inside `rescue()` and can never
fail the domain write.

| Event | Fires from | Payload (`resource`) |
|---|---|---|
| `consent.granted` | `ConsentService::approveConsent` | grant_id, health_id, facility_id, scope, status, granted_at, expires_at |
| `lab_result.released` | `LabDeskService::release` (also reached via `LabDeskWebService::release`) | lab_order_id, health_id, facility_id, test_code, test_name, status, released_at |
| `prescription.issued` | `PrescriptionService::issue` | prescription_id, reference, health_id, facility_id, status, item_count, prescribed_at, expires_at |

Delivery is facility-scoped: `dispatch()` persists a `webhook_events` row
(`facility_id`, `client_id` added to the model's fillable — they were silently
dropped before) and queues one `SendWebhookJob` on the `webhooks` queue per active
subscription whose `facility_id` matches (`WebhookSubscription.facility_id` is
now fillable — `storeWebhook` had been writing it into the void). The job signs
`t.<json>` with HMAC-SHA256 using the subscription secret
(`X-OpesCare-Signature: t=…,v1=…`), logs to `webhook_delivery_logs`, retries
3× with backoff, and marks `exhausted` on the last failure.

**Replay:** `POST /developer/webhooks/deliveries/{delivery}/replay`
(`portals.developer.webhooks.replay`) — shown as a button on failed/exhausted
rows of `/developer/webhooks`. Own subscriptions only (foreign → 404), paused
endpoint refused, same `event_id` + payload re-queued to the same subscription so
the attempt count continues on the row the partner clicked; a `webhook_replays`
row and an audit event are written.

### D-9 — consents page
`GET /developer/consents[?app=<own uuid>]` (`portals.developer.consents`, linked
from all three developer-tier sidebars). For each facility-bound app: the
`consent_grants` rows with `status = active` and not expired for that facility —
Health ID (resolved by primary key from the grant, never searched), scope,
granted, expires, status. Facility-less apps get the explainer only. The
request flow is API-side; the page links `/docs/api#consent` and `/docs/webhooks`.

### D-10
`resources/views/portals/admin/_admin_nav.blade.php` already carried both links
(Partners section: Developer Accounts, Production Requests with a pending badge)
when this pass started; verified, nothing to add.

## Deliberately not built (pass 1 list, kept for history)
* Consent-grant browser (D-9): grants are facility-scoped; a portal-created
  client has no facility, so a page would be empty by construction. Needs the
  D-5 product decision (how a partner app is bound to a facility) first.
* Rewriting analytics (D-7) onto `api_usage_logs`: attribution there is a
  caller-supplied header; fixing that means changing `LogApiUsage` (global
  middleware) — out of scope. The activity page shows the honest per-client
  counts from `fhir_ingest_events` + `api_usage_logs` instead.
* Webhook replay from the portal: `WebhookService::replay` needs a
  `webhook_events` row; none is created because `dispatch()` is never called.
* The admin sidebar link to production-request approvals (D-10): not owned by
  this pass.
* Wiring `WebhookService::dispatch` into consent/lab/prescription writes.
* Seeding a `developer_accounts` row for `demo.developer@` on prod (seeder is
  not this pass's file; the onboarding form creates it on first visit once
  D-1 is fixed).
