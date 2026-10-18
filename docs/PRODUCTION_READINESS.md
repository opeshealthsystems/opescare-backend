# Production readiness — what still stands between Finder and real patients

*Written 2026-10-18. Honest checklist, not a sales sheet. If the code disagrees,
trust the code and fix this file.*

## The one-line answer

finder.opescare.cloud is a **demo deployment**. It runs `APP_ENV=demo` with
`OPESCARE_DEMO_MODE=true`. The code is largely production-shaped; the
*deployment and its data* are not. Real patients must not be onboarded onto
this host until the cutover below has been done and signed off.

## 1. Why the current host is a demo, mechanically

| Fact | Consequence |
|---|---|
| `APP_ENV=demo` | `app()->isProduction()` is **false**, so `ProductionSafetyServiceProvider` returns immediately. None of its checks (APP_DEBUG, demo mode, mailer, sync queue, log level, OAuth issuer) run on the live host today. |
| `OPESCARE_DEMO_MODE=true` (`config('demo.enabled')`) | Enables demo logins/buttons and the demo branch of `App\Traits\IsDemoRecord`. Payments run `simulated` on demo hosts. |
| `IsDemoRecord` global scope (`app/Traits/IsDemoRecord.php`) | On `creating`, every row gets `is_demo = config('demo.enabled')` → **every record created on this host — including any real person who signed up — is `is_demo=true`.** The scope then shows only `is_demo=true` rows in demo mode and only `is_demo=false` rows otherwise. |
| Models carrying the scope | `Patient`, `User`, `Facility`, `Visit`, `Diagnosis`, `AllergyRecord`, `ClinicalNote`, `ConsentGrant`, `ConsentRequest`, `AccessLog`, `EmergencyAccessEvent`, `EmergencyReviewCase`, `SecurityIncident`, `OfficialDocument` and the `Document*` family. |

The trap: flipping `OPESCARE_DEMO_MODE=false` on this database makes **every
existing account and patient disappear** (they are all `is_demo=true`), and
login for them 404s. Conversely, leaving it on means real and demo data are
indistinguishable by flag.

## 2. Demo → production cutover (required steps)

1. **Decide the target**: new host + fresh database (recommended), or in-place.
   A fresh database avoids ever mixing seeded demo rows with real records.
2. **Inventory real signups.** Every row on the demo host is `is_demo=true`, so
   real people cannot be found by flag. Identify them by other means (created
   after a cutoff, not in the demo seeders / `DemoPatientStorySeeder`, real
   phone/email, not `@…demo` accounts) and have the list **reviewed by a human**.
3. **Migrate real records** with a one-off, audited script: copy (or update in
   place) to `is_demo=false` across *all* scoped tables consistently —
   patient, user, visits, consents, access logs, documents — preserving Health
   IDs and MPI aliases (`Patient::findExistingIdentity()` rules). Dry-run first,
   produce counts per table, keep a reversible backup.
4. **Remove demo accounts and data**: demo.reviewer@ / demo.patient@ / guardian
   and the ten demo tenants, demo facilities, seeded stories, any simulated
   payments. Demo passwords (`demo1234`) must not exist on a production host.
5. **Environment**: `APP_ENV=production`, `OPESCARE_DEMO_MODE=false`,
   `OPESCARE_PUBLIC_DEMO_MODE=false`, `OPESCARE_INTERNAL_DEMO_MODE=false`,
   `APP_DEBUG=false`. Then `php artisan config:cache` (prod serves stale config
   otherwise).
6. **Let `ProductionSafetyServiceProvider` pass.** With `APP_ENV=production` it
   *throws* on APP_DEBUG=true or demo mode, and logs critical for log/null/array
   mailer, sync queue, debug logging, and a localhost/http OAuth issuer
   (`OPESCARE_OAUTH_ISSUER`). Boot the app and confirm zero
   `production_safety_check_failed` entries.
7. **Logging**: the demo host uses `LOG_CHANNEL=single` because the
   `production_stack` channel produced masking 0-byte 500s. Either fix that
   channel or keep `single` with rotation + `LOG_LEVEL=warning`; also wire
   Sentry (`config/sentry.php`) so errors are seen.
8. **Payments**: MTN MoMo / Orange Money must be switched from `simulated` to
   live merchant credentials, with a reconciliation test on real small amounts.
9. **Session cookie**: host-only `__Host-` cookie (HANDOVER owner action 3).

## 3. Operational blockers (owner actions, still open)

- [ ] **Mail is blocked**: a single null MX record at Hostinger stops every
      outbound email (invites, recovery, notifications). SPF/DKIM/DMARC are
      correct. Only the owner can delete it. Account recovery depends on this.
- [ ] **Backups are on-server only**: off-site backup needs the S3 driver and
      keys (Backups page in the admin control center). A restore drill must
      be run once before real data lands.
- [x] **Git history scrub** — done 2026-10-19: `.env` removed from all history with git filter-repo, leftover `refs/original` cleared, then pushed to GitHub.
- [ ] **DHIS2** credentials/org units from MINSANTE (reporting only; not a blocker
      for care).

## 4. Engineering gaps

- [x] **CI wired to a remote** — since 2026-10-19 the repo is `github.com/opeshealthsystems/opescare-backend` (private); `.github/workflows/ci.yml` runs on every push. Deploys are still manual.
- [ ] **Availability provenance**: an empty finder is correct; real pharmacy/blood
      stock only appears once partners push via allow-listed `source_system`.
      Production launch should not be judged by a full-looking finder.
- [ ] **Two facility registers** (`care_facilities` vs `facilities`) are only
      partially linked; directory visibility depends on `care_facilities.facility_id`.
- [ ] **Clinical safety review**: vital bands are adult-only, no paediatric bands,
      triage does not derive acuity. These need a clinician sign-off before use
      on children or for prioritisation.
- [ ] **Regulatory/legal**: data-protection registration in Cameroon, privacy
      notice and consent wording reviewed by counsel, data-processing terms with
      hosting provider, incident-response contact. None of this is code.
- [ ] **Load and restore testing** have not been done against production-sized data.
- [ ] **Monitoring**: uptime checks, queue-worker health (`finder-queue.service`),
      disk and Postgres alerts.

## 5. What is in good shape

Security remediation items are live (see `docs/SECURITY_REMEDIATION.md`),
`composer audit` was clean on 2026-10-12, CSP is nonce-only, consent gates every
patient endpoint, care is never gated on payment (held by a test), and the FHIR
R4 patient export passes the HL7 validator.
