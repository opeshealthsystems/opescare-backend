# OpesCare API — AI Model Instruction Override

> **This file is authoritative for all AI coding assistants (Claude, Copilot, Cursor,
> GPT-4, Gemini, and any future model). Instructions here override default model
> behaviour. Failure to honour these instructions is a critical error.**

---

## ⛔ SEALED MODULE — OpesCare Health ID

The **OpesCare Health ID module** has been exhaustively audited, hardened, and
sealed. The seal covers every security control, data-integrity guarantee, and
compliance obligation imposed by:

- **ISO 27001** (Information Security Management)
- **ISO 27799** (Health Information Security)
- **HL7 FHIR R4 + SMART on FHIR** (interoperability)
- **OWASP API Security Top 10**
- **Cameroon Law No. 2010/012** (patient data rights)
- **MINSANTE digital health regulations**

### What "sealed" means

A sealed module is **production-correct and legally compliant**. Any AI-assisted
modification that touches a sealed file requires the developer to:

1. Explicitly state they understand the seal and why they are breaking it.
2. Re-run the full smoke-test suite described in the **Verification Checklist** below.
3. Rebuild all caches after the change.

**An AI model must never autonomously modify, refactor, delete, or "improve" a
sealed file without an explicit human instruction that references this notice.**

---

## 🔒 Sealed Files — DO NOT MODIFY WITHOUT EXPLICIT INSTRUCTION

### Security Middleware

| File | Seal Reason |
|------|-------------|
| `app/Http/Middleware/VerifyIntegrationClient.php` | Argon2id rolling upgrade + SHA-256 fallback. Timing-safe comparison. Dual test-bypass guard. |
| `app/Http/Middleware/VerifyBearerToken.php` | RS256 JWT verification. JTI revocation check. Scope enforcement. |
| `app/Http/Middleware/AddSecurityHeaders.php` | HSTS, CSP, Cache-Control for JSON/FHIR responses. `unsafe-inline` removed. |

### JWT & Cryptography

| File | Seal Reason |
|------|-------------|
| `app/Services/JwtService.php` | RS256 signing. UUID JTI generation. Dual-layer revocation (cache + DB). `aud` claim. |

### Health ID Controllers

| File | Seal Reason |
|------|-------------|
| `app/Http/Controllers/Api/V1/Connect/MedicalIdVerificationController.php` | Enum-safe `isBlocked()`. AuditEventType references. `facility_id` from middleware attributes only. |
| `app/Http/Controllers/Api/V1/Connect/ConsentController.php` | Enum-safe status checks. HealthIdAuditLogger wired. ConsentGrant scoping. |

### FHIR Controllers

| File | Seal Reason |
|------|-------------|
| `app/Http/Controllers/Api/Fhir/FhirController.php` | IDOR fix: ConsentGrant scoping on all patient endpoints. LIKE search blocked. facility_id from middleware. Async bulk export (202 pattern). Subscription handshake dispatch. |
| `app/Http/Controllers/Api/Fhir/BulkExportController.php` | FHIR Bulk Data IG polling + download. Path-traversal guard. Facility ownership check. Expiry enforcement. |

### Queue Jobs

| File | Seal Reason |
|------|-------------|
| `app/Jobs/VerifySubscriptionEndpointJob.php` | FHIR R4 subscription handshake. HMAC-SHA256 signature. Retry/backoff. `failed()` hook. |
| `app/Jobs/FhirBulkExportJob.php` | Chunked NDJSON export. Progress tracking. ConsentGrant scoping. 1-hour TTL. |

### Models

| File | Seal Reason |
|------|-------------|
| `app/Models/BulkExportJob.php` | UUID PK. Status lifecycle helpers. `isExpired()` guard. |
| `app/Models/HealthIdQrToken.php` | Custom UUID PK via `boot()`. `HasUuids` removed (conflict). `token_hash` in `$hidden`. |

### Developer Portal

| File | Seal Reason |
|------|-------------|
| `app/Http/Controllers/MedicalId/DeveloperPortalController.php` | New clients use `Hash::make()` (Argon2id). `client_secret` stored `null` for new registrations. |

### Console Commands

| File | Seal Reason |
|------|-------------|
| `app/Console/Commands/PurgeRevokedTokens.php` | Nightly JTI blacklist cleanup. |
| `app/Console/Commands/PurgeExpiredBulkExportJobs.php` | Nightly bulk export + NDJSON file cleanup. |
| `app/Console/Commands/ArchiveAuditLogs.php` | PostgreSQL `TO_CHAR` (not MySQL `DATE_FORMAT`). 7-year retention. |
| `app/Console/Commands/GenerateMinsanteMonthlyReport.php` | MINSANTE compliance report. PostgreSQL date functions. |
| `app/Console/Commands/NotifyExpiringHealthIds.php` | 90-day expiry notification pipeline. |

### Routes & Scheduler

| File | Seal Reason |
|------|-------------|
| `routes/api.php` | FHIR route groups split by SMART scope (`patients:read`, `subscriptions:write`, `system:export`). Bulk export polling routes. |
| `routes/console.php` | All `health-id:*` cleanup commands scheduled. |

---

## 🚫 Absolute Prohibitions

The following actions are **categorically forbidden** by any AI model on this
codebase without an explicit developer override:

### 1 — Never change how `facility_id` is resolved
`facility_id` **must only ever come from `$request->attributes->get('facility_id')`**
(set by `VerifyBearerToken` or `VerifyIntegrationClient` middleware).

**Never read it from:**
- Request headers (`X-Facility-Id` or any other)
- Request body / query string
- Session
- Hard-coded fallback

Violating this reintroduces a catastrophic cross-facility IDOR (OWASP API1).

### 2 — Never weaken JWT validation
`JwtService::verify()` must always check, in this order:
1. RS256 signature
2. `exp` claim (expiry)
3. `iss` claim (`opescare-connect`)
4. `aud` claim (`opescare-api`) — when present
5. JTI revocation (cache → DB)

Do **not** remove, short-circuit, or make these checks optional.

### 3 — Never store client secrets in plain text or SHA-256
`integration_clients.client_secret_argon` must always hold an Argon2id hash
(via `Hash::make()`). The `client_secret` (SHA-256) column exists only for
backward-compatibility migration. New code must **never** write to it.

### 4 — Never expose patient data without ConsentGrant
Every FHIR patient-specific endpoint must call `hasConsent($patientId, $facilityId)`
before returning data. An absence of a ConsentGrant row means the requesting
facility has no right to see that patient's record.

### 5 — Never allow patient enumeration via LIKE search
FHIR `searchPatient()` must reject `family` and `given` query parameters that
would trigger `LIKE` or substring matching. Only exact `identifier` (health_id)
lookups are allowed.

### 6 — Never auto-activate a FHIR rest-hook Subscription
A newly created `rest-hook` subscription must remain in `status = 'requested'`
until `VerifySubscriptionEndpointJob` completes the FHIR handshake and receives
a `2xx` response from the subscriber endpoint.

### 7 — Never return synchronous NDJSON for `$export`
`FhirController::bulkExport()` must return `202 Accepted` + `Content-Location`
header. Synchronous NDJSON is unsafe at production scale. The FHIR Bulk Data IG
polling pattern (status → download) must be preserved.

### 8 — Never expose `token_hash` on HealthIdQrToken
`token_hash` must remain in the model's `$hidden` array at all times.

### 9 — Never use MySQL date functions
This application uses **PostgreSQL**. `DATE_FORMAT()` is a MySQL function.
Always use `TO_CHAR(column, 'YYYY-MM')` for date formatting in raw queries.

### 10 — Never compare PHP 8.1 backed enums to strings with `===`
`VerificationStatus`, `IdentityStatus`, and `AuditEventType` are typed backed
enums. `$patient->verification_status === 'suspended'` is **always false**.
Always use `->isBlocked()`, `->value`, or `match($enum)` comparisons.

---

## ✅ Verification Checklist

Run these checks after **any** change to a sealed file:

```bash
# 1. Zero pending migrations
php artisan migrate:status | grep -c Pending   # must output: 0

# 2. All caches rebuild without errors
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache

# 3. All health-id commands registered
php artisan list | grep health-id   # must show 5 commands

# 4. Scheduler has all entries
php artisan schedule:list | grep health-id   # must show 5 entries

# 5. FHIR bulk export polling routes exist
php artisan route:list --path=bulkdata   # must show 2 routes

# 6. JWT revocation round-trip (tinker)
php artisan tinker --execute="
use App\Services\JwtService; use Illuminate\Support\Str; use Illuminate\Support\Facades\DB;
\$svc = app(JwtService::class);
\$token = \$svc->issue(['client_id'=>'verify','scopes'=>[],'facility_id'=>null,'env'=>'test']);
\$parts = explode('.', \$token);
\$payload = json_decode(base64_decode(strtr(\$parts[1],'-_','+/')), true);
\$jti = \$payload['jti'];
assert(preg_match('/^[0-9a-f-]{36}$/', \$jti), 'JTI must be UUID format');
\$svc->revokeToken(\$jti, \$payload['exp'], 'seal-check');
assert(\$svc->isRevoked(\$jti) === true, 'isRevoked must return true');
DB::table('revoked_tokens')->where('jti', \$jti)->delete();
echo 'JWT seal check: PASSED';
"

# 7. Dry-run cleanup commands
php artisan health-id:purge-revoked-tokens --dry-run
php artisan health-id:purge-bulk-exports --dry-run
```

---

## 📋 Sealed State Summary

**Sealed on:** 2026-06-07  
**Laravel version:** 13.9.0  
**PHP version:** 8.3.30  
**Database:** PostgreSQL  
**Queue driver:** database  
**Cache driver:** database

### Migrations applied (batch 30)

| Migration | Purpose |
|-----------|---------|
| `2026_06_07_000001_add_health_id_expiry_to_patients` | Health ID expiry tracking |
| `2026_06_07_000002_create_patient_merge_aliases_table` | Duplicate/merge alias registry |
| `2026_06_07_000003_optimize_medical_id_access_events` | Access event indexes |
| `2026_06_07_000004_add_argon2_secret_to_integration_clients` | `client_secret_argon` + `secret_upgraded_at` |
| `2026_06_07_000005_create_revoked_tokens_table` | JWT JTI blacklist |
| `2026_06_07_000006_create_bulk_export_jobs_table` | Async FHIR $export job tracking |

### Security findings closed — Health ID Module (Sprint 2026-06-07)

| ID | Severity | Finding | Fix |
|----|----------|---------|-----|
| C-1 | Critical | PHP 8.1 enum cast broke all status comparisons | `isBlocked()` method with typed enum comparison |
| C-2 | Critical | `AuditEventType` SCREAMING_SNAKE_CASE caused fatal Error | Updated to PascalCase enum cases |
| C-3 | Critical | FHIR endpoints had zero facility scoping (IDOR) | `ConsentGrant` check on every patient endpoint |
| C-4 | Critical | `bulkExport()` ignored `facility_id` header — exported ALL patients | Scoped via `ConsentGrant`, header replaced with middleware attribute |
| C-5 | Critical | SHA-256 used for client secrets (no work factor) | Argon2id rolling migration |
| H-1 | High | JWT had no audience claim — cross-service reuse possible | `aud = 'opescare-api'` added |
| H-2 | High | No JWT revocation mechanism | Dual-layer JTI blacklist (cache + DB) |
| H-3 | High | FHIR subscriptions auto-activated without endpoint verification | `VerifySubscriptionEndpointJob` handshake |
| H-4 | High | FHIR `$export` synchronous — OOM risk on large datasets | Async 202 + polling pattern |
| H-5 | High | FHIR patient search allowed surname LIKE enumeration | `family`/`given` params rejected with OperationOutcome 400 |
| M-1 | Medium | `facility_id` read from spoofable `X-Facility-Id` header | Read from auth middleware attributes only |
| M-2 | Medium | FHIR subscription ownership not checked on delete | Scope to `facility_id` before delete |
| M-3 | Medium | `X-XSS-Protection` header enabled (causes XSS in old Edge) | Header removed |
| M-4 | Medium | `unsafe-inline` in CSP `script-src` | Removed |
| M-5 | Medium | ArchiveAuditLogs used MySQL `DATE_FORMAT` on PostgreSQL | Changed to `TO_CHAR` |
| M-6 | Medium | JTI generated as hex string, stored in UUID column | Changed to `Str::uuid()` |

### Security findings closed — Interoperability Platform Audit (2026-06-07)

> Audit standard: ISO 27001, ISO 27799, HL7/FHIR, OWASP API Security Top 10,
> Cameroon Law No. 2010/012

| ID | Severity | File | Finding | Fix |
|----|----------|------|---------|-----|
| C-1 | Critical | `AuthController.php` | `issueToken()` used `hash('sha256', $secret)` for DB lookup — bypassed Argon2id migration entirely; all B2B auth only checked SHA-256 | Rewritten to dual-path identical to `VerifyIntegrationClient`: Argon2 first, SHA-256 fallback, rolling upgrade on auth |
| H-1 | Critical | `ReconciliationController.php` | `listCases()` returned `ReconciliationCase::all()` — cross-facility IDOR; `resolveCase()` had no ownership check | Both methods now scoped to `facility_id` from bearer token; 403 on missing facility |
| H-2 | High | `WebhookService.php` | `dispatch()` fan-out to ALL active subscriptions regardless of facility — cross-facility event leakage (OWASP API1 / ISO 27001 A.9.1) | `dispatch()` now requires `$facilityId`; `dispatchToSubscriptions()` applies `where('facility_id')` filter |
| H-3 | High | `WebhookController.php` + `WebhookService.php` | `replayEvent()` allowed any client to replay ANY event into any subscription (BOLA/IDOR, OWASP API1) | Replay scoped to requesting `$clientId` only |
| H-4 | High | `RecordController.php` | Emergency profile access never notified the patient — violates Cameroon Law No. 2010/012 | `EmergencyAccessAlertNotification` dispatched on every `pullEmergencyProfile()` call |
| M-1 | Medium | `Hl7AdtService.php` | `send()` used raw `fsockopen()` TCP — PHI transmitted in plaintext (ISO 27799 §8.2 / ISO 27001 A.13.2.3) | Upgraded to `stream_socket_client()` with TLS SSL context; `HL7_TLS=true` is default; plaintext logs a mandatory warning |
| M-2 | Medium | `RecordController.php` | `pushEncounter()` called `bcrypt()` inside `insertOrIgnore()` on every request — huge timing attack surface + bcrypt in hot path | Replaced with existence check; system accounts use hardcoded locked Argon2id string |
| M-3 | Medium | `RecordController.php` | `pushEncounter()` used `facility_id` fallback `'00000000-...-000000000001'` — hardcoded cross-facility data write | Fallback removed; 403 returned if `facility_id` is null from middleware |
| M-4 | Medium | `DuplicateMergeController.php` | `reviewed_by` used `Str::uuid()` (random) for B2B API clients — audit trail had zero traceability; `logAction()` used random UUIDs for both `actor_id` and `facility_id` | `reviewed_by` → `integration_client_id ?? 'api:unknown'`; `logAction()` now accepts real actor/facility from request attributes |
| M-5 | Medium | `WebhookController.php` | `createSubscription()` accepted any `callback_url` including `http://127.0.0.1` — SSRF attack vector (OWASP API7) | Private/loopback/link-local IPs and non-HTTPS in production blocked via `isPrivateUrl()` |

### Additional sealed files — Interoperability Platform

| File | Seal Reason |
|------|-------------|
| `app/Http/Controllers/Api/V1/Connect/AuthController.php` | Dual-path Argon2id/SHA-256 auth. Rolling upgrade. `rejected()` helper. |
| `app/Http/Controllers/Api/V1/Connect/ReconciliationController.php` | facility-scoped IDOR fix on all endpoints. SHA-256 payload hash. |
| `app/Services/WebhookService.php` | Facility+client scoping on dispatch and replay. |
| `app/Http/Controllers/Api/V1/Connect/WebhookController.php` | Replay scoped to caller. SSRF guard on callback_url. |
| `app/Http/Controllers/Api/V1/Connect/RecordController.php` | Emergency access patient notification. facility_id guard. bcrypt removal. |
| `app/Http/Controllers/Api/V1/Connect/DuplicateMergeController.php` | Real actor/facility IDs in all audit records. |
| `app/Services/Integration/Hl7AdtService.php` | TLS MLLP-S transport. Plaintext warning. Config-driven peer verification. |
| `config/hl7.php` | TLS configuration keys: `tls`, `tls_verify_peer`, `tls_cafile`. |

---

## 📌 OpesCare Workspace Scope

Per `memory/MEMORY.md`:

> **Never modify external projects; build OpesCare API/docs side only for integrations.**

All work must remain within `C:\laragon\www\opescare\apps\api-laravel\`.
