# OpesCare STRIDE Threat Model

**Version:** 1.0
**Owner:** Platform Engineering / Information Security
**Date:** 2026-05-27
**Review cadence:** Annually or after major architectural change

---

## Overview

This document applies the STRIDE threat modelling methodology to the OpesCare
Electronic Health Record (EHR) platform. OpesCare is a multi-tenant, API-first
healthcare platform serving facilities in Gabon, storing highly sensitive
Protected Health Information (PHI).

**STRIDE categories:**

| Letter | Threat | Security Property Violated |
|--------|--------|---------------------------|
| S | Spoofing | Authentication |
| T | Tampering | Integrity |
| R | Repudiation | Non-repudiation |
| I | Information Disclosure | Confidentiality |
| D | Denial of Service | Availability |
| E | Elevation of Privilege | Authorization |

---

## System Components

| Component | Description |
|-----------|-------------|
| Patient Portal (Blade) | Web UI for patients and guardians |
| Laravel API | REST API backend, PHP 8.3 / Laravel 13 |
| PostgreSQL Database | Primary data store (PHI at rest) |
| Redis | Queue, cache, session store |
| Horizon Dashboard | Queue monitoring (admin-only) |
| Integration Partners | External systems (MTN MoMo, Orange, DHIS2) |
| AWS S3 | Encrypted backup storage |
| AWS KMS | Envelope encryption key management |

---

## Data Flow Diagram (Text)

```
[Patient Browser] ──HTTPS──► [Cloudflare WAF]
                                    │
                                    ▼
                             [Load Balancer]
                                    │
                                    ▼
                          [Laravel API / Nginx]
                           │         │        │
                           ▼         ▼        ▼
                       [PostgreSQL] [Redis] [S3/KMS]
                                    │
                                    ▼
                          [Integration Partners]
                          (MTN MoMo, Orange, DHIS2)
```

---

## Threat Analysis

### S — Spoofing

**Threat S1: Patient identity spoofing via stolen credentials**
- Component: Authentication endpoint (`/api/auth/login`)
- Likelihood: High (credential theft is common)
- Impact: Critical (access to PHI of another patient)
- Current mitigations:
  - Sanctum token Authentication
  - Per-facility role assignment check (EnsurePortalAccess)
  - Brute-force protection: 5 attempts/min throttle on auth routes
- Residual risk: Phishing attacks not fully mitigated — requires MFA (future)
- Recommended action: Implement TOTP/SMS MFA for patient login

**Threat S2: Integration partner spoofing via leaked API key**
- Component: VerifyIntegrationClient middleware
- Likelihood: Medium
- Impact: High (bulk PHI access)
- Current mitigations:
  - API key stored as SHA-256 hash in DB (never plain text)
  - X-Integration-Client-Id validated on every request
  - Per-partner trust level enforcement (VerifyPartnerTrustLevel)
- Recommended action: Add API key rotation support; enforce mTLS for FHIR partners

**Threat S3: Guardian spoofing a dependent's identity**
- Component: Family account / FamilyLink module
- Likelihood: Low (requires compromised guardian account)
- Impact: High (access to minor's PHI)
- Current mitigations:
  - GuardianAccessMiddleware validates relationship before data access
  - Audit log for every guardian access event
  - Age-transition workflow removes guardian access at 18

---

### T — Tampering

**Threat T1: Direct database manipulation bypassing audit log**
- Component: PostgreSQL database
- Likelihood: Low (requires DB credentials)
- Impact: Critical (tampered medical records, liability)
- Current mitigations:
  - DB credentials never in source code (.env.example uses placeholders)
  - Append-only audit_log table (no UPDATE/DELETE permitted)
  - Secrets rotation policy: DB password rotated every 60 days
- Recommended action: Enable PostgreSQL row-level security; implement pgaudit

**Threat T2: Request body tampering between client and API**
- Component: All API endpoints
- Likelihood: Low (HTTPS in transit)
- Impact: Medium
- Current mitigations:
  - TLS 1.2+ enforced at WAF
  - HSTS header on all responses
  - CSP and security headers via SecurityHeaders middleware
- Recommended action: Add request signing for integration partner webhooks

**Threat T3: Tampering with encrypted patient PII fields**
- Component: Patient model encrypted fields (CNAMGS, phone, etc.)
- Likelihood: Very Low
- Impact: High
- Current mitigations:
  - AES-256-GCM encryption with authentication tag (tamper-evident)
  - KMS-managed keys with domain separation (HMAC label `opescare:kms:field-encryption:v1`)
  - `reEncrypt()` available for key rotation

---

### R — Repudiation

**Threat R1: Provider denies accessing a patient record**
- Component: EmergencyAccessController, RecordController
- Likelihood: Medium (disputes are common in healthcare)
- Impact: High (compliance, legal liability)
- Current mitigations:
  - Append-only security_audit_logs table records every record access
  - actor_id from authenticated session (never caller-supplied)
  - Audit entries include: actor, patient, action, timestamp, IP, facility
- Residual risk: Audit log is in same DB — DB admin could delete entries
- Recommended action: Ship audit logs to immutable external store (CloudWatch Logs / S3 with Object Lock)

**Threat R2: Patient denies granting consent**
- Component: Consent management module
- Likelihood: Low
- Impact: High (HIPAA compliance)
- Current mitigations:
  - Consent grants stored with created_at, revoked_at, and grantor patient_id
  - Consent grant ownership verified before any patient_id filter

---

### I — Information Disclosure

**Threat I1: Patient PHI exposed via over-broad API response**
- Component: All API controllers returning patient data
- Likelihood: Medium
- Impact: Critical (HIPAA violation)
- Current mitigations:
  - Eloquent API resources strip sensitive fields from responses
  - Facility ownership checks prevent cross-facility data leakage
  - Demo patients excluded from production admin views
- Recommended action: Automated PII scanning on API response payloads

**Threat I2: PHI in application logs**
- Component: Logging system (daily log channel)
- Likelihood: Medium (accidental logging is common)
- Impact: High
- Current mitigations:
  - Log level set to `warning` in production (reduces verbose output)
  - Log retention set to 90 days
  - User-agent sanitized before logging in DemoAccessController
- Recommended action: Add structured log PII scrubbing middleware

**Threat I3: PHI in error responses**
- Component: Exception handler, VerifyIntegrationClient
- Likelihood: Low
- Impact: High
- Current mitigations:
  - DB errors redacted from 500 responses in VerifyIntegrationClient
  - SDK scope names removed from 403 responses
  - Production APP_DEBUG=false enforced via ProductionSafetyServiceProvider

**Threat I4: PHI in backups accessible to unauthorized parties**
- Component: spatie/laravel-backup → S3
- Likelihood: Low
- Impact: Critical
- Current mitigations:
  - Backups AES-256 encrypted before upload
  - S3 bucket must have SSE-S3 or SSE-KMS enabled (infrastructure config)
  - Backup password stored in secrets manager (not source code)

---

### D — Denial of Service

**Threat D1: API flood from unauthenticated bots**
- Component: All public API endpoints
- Likelihood: High
- Impact: High (availability)
- Current mitigations:
  - Cloudflare WAF bot management (infrastructure layer)
  - Laravel rate limiter: 60 req/min per IP (unauthenticated)
  - Health check endpoint cached at edge
- Recommended action: Enable Cloudflare Under Attack Mode during incidents

**Threat D2: Legitimate user rate-limited during traffic spike**
- Component: Rate limiter (AppServiceProvider)
- Likelihood: Medium
- Impact: Medium (user experience)
- Current mitigations:
  - Integration partners get 1200 req/min (vs 600 for regular users)
  - Rate limit responses include Retry-After headers
- Recommended action: Implement adaptive rate limiting based on server load

**Threat D3: Queue exhaustion via bulk job submission**
- Component: Redis queue / Laravel Horizon
- Likelihood: Low
- Impact: High (async processing halts)
- Current mitigations:
  - Horizon supervisor configured with maxProcesses limits
  - Memory limit per worker: 256 MB
  - Failed jobs captured in failed_jobs table for replay
- Recommended action: Add per-queue max job size limits

---

### E — Elevation of Privilege

**Threat E1: Regular patient accesses admin functionality**
- Component: AdminPortalController, Horizon dashboard
- Likelihood: Low
- Impact: Critical
- Current mitigations:
  - EnsurePortalAccess checks per-facility role on every request
  - Horizon dashboard gated by viewHorizon gate (admin/super-admin only)
  - Admin portal routes protected by dedicated middleware group
- Recommended action: Implement ABAC for fine-grained permissions

**Threat E2: Facility staff accesses another facility's data**
- Component: All controllers with patient data
- Likelihood: Medium (shared codebases, misconfiguration)
- Impact: Critical (cross-tenant PHI leak)
- Current mitigations:
  - RequireFacilityContext middleware enforces X-Facility-Id on all API requests
  - HasFacilityScope trait for opt-in row-level facility filtering
  - AdminPortalController scoped to current facility
- Recommended action: Promote HasFacilityScope to a global scope on all Patient-related models

**Threat E3: Mass assignment of privileged fields**
- Component: All Eloquent models
- Likelihood: Low
- Impact: High (e.g., setting is_admin, role_id via API)
- Current mitigations:
  - role_id removed from User.$fillable
  - is_demo removed from Patient.$fillable
  - All models use explicit $fillable (no $guarded = [])

---

## Risk Summary

| ID | Threat | Likelihood | Impact | Risk | Status |
|----|--------|-----------|--------|------|--------|
| S1 | Patient credential spoofing | High | Critical | **CRITICAL** | Mitigated (MFA needed) |
| S2 | Integration partner spoofing | Medium | High | **HIGH** | Mitigated |
| S3 | Guardian identity spoofing | Low | High | **MEDIUM** | Mitigated |
| T1 | DB direct manipulation | Low | Critical | **HIGH** | Mitigated |
| T2 | Request tampering | Low | Medium | **LOW** | Mitigated |
| T3 | Encrypted field tampering | Very Low | High | **LOW** | Mitigated |
| R1 | Provider denies access | Medium | High | **HIGH** | Partial (needs external audit log) |
| R2 | Consent denial | Low | High | **MEDIUM** | Mitigated |
| I1 | PHI in API responses | Medium | Critical | **CRITICAL** | Mitigated |
| I2 | PHI in logs | Medium | High | **HIGH** | Partial |
| I3 | PHI in error responses | Low | High | **MEDIUM** | Mitigated |
| I4 | PHI in backups | Low | Critical | **HIGH** | Mitigated |
| D1 | API flood | High | High | **HIGH** | Mitigated |
| D2 | User rate-limiting | Medium | Medium | **MEDIUM** | Mitigated |
| D3 | Queue exhaustion | Low | High | **MEDIUM** | Mitigated |
| E1 | Privilege escalation to admin | Low | Critical | **HIGH** | Mitigated |
| E2 | Cross-facility data access | Medium | Critical | **CRITICAL** | Mitigated |
| E3 | Mass assignment | Low | High | **MEDIUM** | Mitigated |

---

## Open Action Items

| Priority | Action | Owner | Due |
|----------|--------|-------|-----|
| P0 | Implement MFA for patient login | Engineering | Q3 2026 |
| P0 | Ship audit logs to immutable S3 with Object Lock | Engineering | Q3 2026 |
| P1 | mTLS for FHIR integration partners | Engineering | Q4 2026 |
| P1 | Structured log PII scrubbing middleware | Engineering | Q4 2026 |
| P2 | Enable pgaudit on PostgreSQL | Platform | Q4 2026 |
| P2 | Promote HasFacilityScope to global scope on Patient models | Engineering | Q4 2026 |
