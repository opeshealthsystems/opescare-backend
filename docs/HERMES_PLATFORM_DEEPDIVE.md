# PLATFORM DEEP-DIVE — technical knowledge for Hermes agent
> Companion to HERMES_PLAYBOOK.md (claims contract). This is the depth layer:
> when a prospect's technical/clinical leadership asks "how does it actually
> work", Hermes answers from HERE. Any statement not traceable to this doc or
> the playbook §2 table must not be made. Repo paths are given so any claim
> is verifiable. Last review: 2026-10-20.

## 1. Architecture in one page (for the "what is it built on" question)

- **Web**: server-rendered Laravel (PHP 8.3) + Blade; servers ~500 views on a
  single OPS design system; PostgreSQL 16; Redis-capable (cache/queue).
- **APIs**: versioned REST under `/api`, JWT bearer for the mobile apps, HMAC
  + scoped keys for partner/integration clients; OpenAPI 3 document is
  publicly served (`public/openapi.json` + a Postman collection).
- **Mobile**: Expo/React Native app (Google Play 1.5.0), Zustand + TanStack
  Query, Expo SecureStore for tokens, AES-256-GCM encrypted offline cache,
  biometric app-lock, push via Expo/FCM.
- **Background work**: queue workers (search jobs app/Jobs), scheduled
  commands (reminders every 5 min, data retention purges, blood-request
  expiry hourly, MPI detection, DHIS2 monthly drafts).
- **Environments**: production opescare.com runs an explicit demo partition
  (`is_demo`) so prospects can evaluate safely; demo accounts can never reach
  platform-tier admin (test-enforced).
- **One honest scale sentence** if asked about load: designed for national
  rollout on PostgreSQL; today single-primary with the hot paths indexed;
  documented scale plan (`docs/SCALE_READINESS.md`). Do not claim "runs at
  100 M users".

## 2. Identity layer — Health ID / OPESCARD (the thing institutions ask most)

**What exists (verified):**
- Every patient gets a **Health ID** (unique, printable, QR-bearing). Identities
  are **provisional or verified**; verification is recorded with the facility
  that did it (`PatientIdentityService`, mobile registration flows).
- The **OPESCARD** QR on the card is a **verification link**, never embedded
  PHI: scanning resolves via `/verify/health-id` or a token-bound QR route —
  designed so the QR alone can't leak name/DOB.
- **Duplicate-identity detection is centralised** in one place
  (`Patient::findExistingIdentity()` — documented "read its docblock before
  touching any patient-creation path") so registration, booking, records and
  import all funnel through one dedup decision.
- Patient lookup keys are hashed/unique-indexed (phone `phone_number_hash`,
  CNAMGS id unique) — identity resolution is by exact match; the FHIR gate
  deliberately rejects fuzzy search so nobody enumerates patients.
- Recovery: phone-based PIN bootstrap + OTP flows (login reset routes).
- Family/guardian model: verified `FamilyLink`s let a guardian act-as a
  dependent in the patient app; guardianship transitions are scheduled and
  administrated (family:check-age-transitions daily job; legal minor-transition desk).
- Account deletion and data-retention purge jobs exist (compliance posture).

**Why institutions care (say this):** "the national directory is public; the
identity layer is deliberate: one ID per patient, dedup at the point of
creation, QR cards that verify without leaking data, and guardian transitions
for minors."

## 3. Master Patient Index (MPI) — "how do you stop double charts?"

**What exists:**
- An **MPI candidate workflow**: a scheduled detector (`mpi:detect`, rate-limited
  batches) scans registered patient hotkeys (national_id normalization,
  phone, alias resolution) and produces `MpiCandidate` rows with
  `pending_review` status. No silent auto-merging: a human decides.
- Merge/reject/**unmerge** are separate, **audited** desk actions
  (`routes` `portals.admin.mpi.*`; unmerge exists because a bad merge must be
  reversible and visible).
- Alias resolution is indexed; matching street addresses/blocks are logged
  rather than merged blindly (block-mate scan uses an expression-match with an
  audit record).
- Where MPI is exposed: platform admin MPI desk (`/admin/mpi`), MPI candidates
  raise/merge/reject, plus a patient-merge record in the facility move flow.

**Say this:** "duplicates are *found* automatically, *decided* by humans, and
*reversible* — with an audit trail on every decision." Do NOT claim automatic
merging or AI matching (the detector is rule/proximity-based and produces candidates).

## 4. Consent, privacy & governance (the differentiator for hospital Data Committees)

- **Consent grants**: a patient may grant a facility scoped, time-limited read
  access; consent state is surfaced in the patient app (pending consent
  requests, ledger). Access to chart data is gated by **treatment basis,
  clinical relationship, facility scope and consent state** (`ClinicalRelationshipGate`,
  `FhirIngestGuard`).
- **Break-glass emergency access**: real override exists — it is
  capability-gated, rate-limited, **logged as an event that notifies the
  patient**, and lands on a review desk so nothing is unreviewed.
- **Patient-visible rights**: export (FHIR/PDF) and correction requests are in
  the patient app; admin governance desk adjudicates them; account deletion is
  in-app with a legal framing.
- **Audit fabric**: access events (who/what/when, per patient), admin action
  log, emergency review cases, suspicious-access flags, breach-of-record
  (breaches) tracking, compliance export desk for a facility's own compendium.
- **Data minimisation**: uploads magic-byte-validated onto a private disk
  served only by signed-time-limited URLs; QR codes never embed raw PHI;
  logs scrub PHI (Sentry scrubbing).
- Cameroon context: data regime Law No 2010/012; screens/modules reference
  retention windows and deletion flows; say "modelled for Cameroon's data law;
  we are happy to run your security checklist" — never "we are certified".

## 5. Interoperability — precise inventory (memorise the deltas)

**LIVE (in production code today):**
1. **FHIR R4 inbound ingest** for partners: DiagnosticReport, Immunization,
   Observation and **transaction bundles** (`routes/fhir.php`). Transport:
   scoped bearer keys per integration client + per-resource scopes; subject
   `reference` rejected; identifier systems allow-listed; facility is always
   re-derived from the verified token (never trusted from the body); **consent
   is re-resolved per entry inside transaction bundles**; every decision writes
   an audit row; failures render FHIR `OperationOutcome`.
2. **Patient FHIR R4 export** (patient-provided bundle) and **PDF export** of
   record pages; the R4 export passes our HL7 validator (was 62 errors → 0).
3. **Record import** into patient charts: FHIR bundle, PDF or image, stored
   **provenance-tagged as patient-provided, unverified** — clinicians see the
   source, decide reliability.
4. **Partner/inventory API**: registered integration clients with scoped keys,
   idempoten mechanics on stock sync (`POST /api/v1/connect/inventory/...`),
   per-item rejection report, quotas, API usage logs, **metered invoicing
   chains exist in the backend** (`ApiBillingService`).
5. **Webhooks/Snippet**: outbound webhooks with **SSRF hardening** (public-host
   check at registration, DNS re-resolution + `CURLOPT_RESOLVE` pinning, no
   redirects), developer portal management.
6. **Document/templates**: certificates and doc templates (URI-signed downloads).
7. **Terminology scaffolding**: code-system mapping desk (SNOMED-ish),
   dictionary search, *gaps* desk showing "does what we recorded still
   resolve", mapping decision log (full audit of maps).
8. **Import engine** for bulk facility data: CSV/mapping wizard with preview,
   approve, **rollback**, and a data-import audit (used by facility data teams
   + pharma catalogue).

**EXISTS ONLY IN BACKEND / NOT SCREENED (tell nobody they can click it):**
- Subscription plans and metered invoice models (no patient-facing
  subscription UI yet).

**NOT built (roadmap — say "on the platform's roadmap" only):**
- Outbound FHIR server (only inbound ingest + patient export exist).
- HL7 v2 interfaces; PACS integration.
- Insurance claims adjudication; subscription billing UI.

**Correct phrases for the demo**: "FHIR R4 ingest today, both for lab
reports and immunisations; export in FHIR and PDF; a partner API with scoped
keys, webhooks and a certification desk to prove your integration in sandbox;
outbound FHIR server and HL7 v2 are env/orouted on the platform roadmap".

## 6. Clinical modules Hermes may demo to a clinical director (surface list)

- **Chart**: the longitudinal patient record — encounters, vitals + charts,
  lab results, immunisations, documents/certificates, referrals (in and out),
  patient-provided imports, allergy/notes context. Data entry via web desks;
  clinical mobile bundle exists for the same content on a phone.
- **Order flow**: prescriptions (pharmacy desk fills them / phones confirm
  receipt), lab orders + worklists + sample collection + results inbox,
  imaging worklist + reports with a timeline, document issuing.
- **Front door**: appointments + slots, waitlist floodgate, queue desk (fast
  track call → start → complete → no-show), triage, referral desk in/out.
- **Continuity**: appointment reminder ladder (48h/24h/2h in-app and push —
  phone/SMS not yet), patient documents expiry, annual summary.
- **Billing**: invoices (patient charges list), collections sessions,
  refunds era second-signature, receipt printing (PDF), a till for a cashier
  role — **institution hypothesis**: "care never gated on payment" is a
  product principle, enforced by tests.
- **Guardrails**: record-level access per clinical relationship / facility
  scope; students/intern roles **read-only** on charts, cannot prescribe or
  assign acuity (deliberate, config-level).

## 7. Pharmacy module (for the chains/prospects)

- Stock desk: batch labels (barcode), expiry, **movements ledger**, counter
  sales + dispense, importers: direct CSV with mapping wizard + preview+
  rollback; medicine catalogue (24 priority tables for the medicine finder);
  price list with history/audit.
- A **status of trust**: the finder's provenance allow-list prints an empty
  result rather than a fake one, so patient search records ARE inventory-ready.
- The availability honesty rule is **test-enforced** (query budget tests,
  provenance tests).

## 8. Operations & readiness (for hospital administrators)

- Facility onboarding pipeline with **go-live readiness scores**; onboarding
  status is per-facility and gated what staff sees/does.
- Certification desk for **partner implementations** (test-run, badge).
- Maintenance desk: feature flags, module toggles, control centre health.
- Push-delivery runbook (docs/push-delivery-runbook.md), observability
  (Sentry, logging, health endpoints), backups (local + optional off-site).

## 9. Known gaps Hermes must handle honestly (duplicated from playbook §9)

Payments sandbox-only · SMS/voice/email channels pending · telehealth video
absent · outbound FHIR/HL7v2/PACS not built · iOS pending · franchise-side
facility "verified" wording banned until claims flow completes.

## 10. The one-pitch you can always fall back on

"OpesCare Finder on opescare.com: a national bilingual health-finder and
working hospital desks on one shared platform. Patients: find live medicine
and blood availability, book appointments, own a verified Health ID with QR
verification, consent-gated records and offline access. Providers: free
facility listing and claim/verify, booking/queue/reception, clinical record,
pharmacy/lab/imaging desks, billing, referrals. B2B: FHIR R4 ingest for
partners with scoped keys, webhooks, sandbox + certification. Built for
Cameroon (EN/FR, FCFA, MoMo-era rails on the roadmap). What we show
tommorrow is sourced or we show nothing — availability provenance is
test-enforced. Android app is live on Google Play today."
