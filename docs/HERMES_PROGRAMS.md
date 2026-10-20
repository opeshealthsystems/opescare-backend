# HERMES PROGRAMS — interoperability campaign, LinkedIn pack, grants, OPES solutions
> Companion to HERMES_PLAYBOOK.md (claims contract), HERMES_PLATFORM_DEEPDIVE.md
> (technical truth), PROPOSALS.md (segment proposals), STUDENTS_PROGRAM.md,
> MARKETING_PLAN.md. Anything in here is part of the same claims-contract
> regime: if a statement isn't traceable, don't publish it.
> Company identity for signatures: operating company **Opesware** (OPES Health
> Systems), Petite Terrain, Bonamoussadi, Douala; contact@opesware.com /
> partners@opesware.com / projects@opesware.com; +237 670 416 238. Public
> catalog: opesware.com/health. Live core platform: opescare.com.
> Last review: 2026-10-20.

---

# PART I — "Join the interoperability layer" campaign (Cameroonian hospitals)

## Track purpose
Message every Cameroonian hospital and clinic about joining the **OPES
interoperability layer** (OPESCare Health ID) — ONE patient identity and ONE
data fabric across systems, departments and partner institutions. This is the
systemic pitch for leadership-level (CEO / Medical Director / IT committee)
contacts, distinct from the segment proposals (which pitch the facility's own
operations desks).

## The pitch, in one sentence per institution type
- **Referral hospitals / CHR / regional hospitals**: "When a patient arrives
  from a district hospital, their identity, allergies, prior labs and
  immunisations arrive with them — under one Health ID — instead of by phone."
- **District hospitals**: "Your lab results follow the patient to the regional
  referral and the bill reflects everything actually done — all through the
  same ID, no re-typing."
- **Private polyclinics/clinics**: "Stop the 'three spellings of one patient'
  problem: automatic duplicate detection with human-decided merges (MPI),
  so your file IS the patient."
- **Diagnostic/imaging**: "Report findings reach the ordering clinician and
  the patient record — your turnaround becomes trackable."
- **All**: "Adopt one system or the whole set; the ecosystem is built to be
  adopted gradually — same Health ID everywhere."

## Technical truths available (quote only these — deep-dive §5)
LIVE TODAY: FHIR R4 inbound ingest (DiagnosticReport, Immunization,
Observation, transaction bundles) with scoped partner keys, per-entry consent
and audit; patient FHIR R4 + PDF export (HL7-validated); patient-provided
record import (provenance-tagged, unverified); Health ID + OPESCARD QR
verification; hashed patient lookups (phone/CNAMGS id), no fuzzy patient
enumeration; central duplicate-detection; terminology mapping desk; CSV
import wizard with preview/rollback; integration sandbox + certification
desk; SSRF-hardened webhooks; opesware's catalogue line: "Twenty-two systems.
One patient record. Every system shares one Health ID."

ROADMAP ONLY (say "on the platform roadmap": outbound FHIR server, HL7 v2
interfaces, PACS integration, UHC/insurance rails beyond records.

## Numbered benefits architecture (campaign framing — reusable in every piece)
1. **One patient, one record** — Health ID dedups at creation; scan QR, verify
   identity, see what the record already knows.
2. **No more re-typing** — partner systems deliver into the record (FHIR R4)
   under provenance tags; your clinicians decide reliability.
3. **Billing completeness** — charges reflect what was actually done across
   desks (care never gated on payment is a wifi principle).
4. **Referral continuity** — referral desk + results inbox flows keep
   orderings visible across sites in the platform today.
5. **An interoperability path that does not require a big-bang migration** —
   adopt the finder + one desk first; bring your HIS/lab feed next via FHIR;
   the certification desk rehearses the integration in sandbox before you
   comrade production.
6. **Sovereignty and compliance** — data minimisation, consent and access
   logging, retention purges, patient-visible access log; modelled for
   Cameroon Law 2010/012 (positioned carefully, never "certified").

## Building the hospital target list (local intel, verified)
- Primary source of truth = the platform's own national directory
  (`care_facilities` — 2,728 listings with type/city), which already lists
  hospitals countrywide (e.g. Hôpital Central de Yaoundé).
- Lookup channels for contacts: hospital websites' "contacts" pages,
  council/association directories, LinkedIn facility pages, phone front desk
  (call first, ask for the Medical Director's administrative assistant).
- Build the list in the **leads desk** (`/admin/leads`, source=interop, channel
  set per first contact) with segments: referral → district → private clinic
  → diagnostic → pharmacy chain. Work referral hospitals first.

## Campaign sequence (per institution)
| Touch | What | When |
|---|---|---|
| 1 | LinkedIn connect + one-paragraph interoperability value + attach nothing | Day 0 |
| 2 | The **Interoperability One-Pager** (EN, FR mirror) — 1 page: illustration of one Health ID + the 6 benefits + deep-dive §5 fact sheet footer | Day 3 |
| 3 | Short call offer (15 min, technical + clinical lead together) | Day 7 |
| 4 | Technical follow-up to whoever attended: the FHIR sandbox + certification path, import wizard, readiness score | Day 14 |
| 5 | Letter-of-understanding offer: pilot one feed (e.g. lab DiagnosticReport ingest) as a scoped, revertible onboarding | Week 4–6 |
Escalation: after 5 touches with no reply, park in a Q2 nurtured list; do
not keep hitting the same contact the same week.

## The Interoperability One-Pager (send-ready body, EN; FR mirror in translations folder)

> **One patient. One record. Every system you run.**
> Cameroon's health system loses value in the seams between its software: the
> lab system that can't see the ward, the pharmacy that re-types
> prescriptions, and a patient who exists three times over under three
> spellings of their name.
> **OPESCare issues one Health ID** and connects every system through it. A
> partner ingest over **FHIR R4** with scoped keys, per-entry consent and an
> audit lane; patient export in FHIR and PDF; automatic duplicate detection
> with human-decided merges; a terminology mapping desk so codes stay
> comparable; an integration sandbox and a certification desk so your
> technical team meets us in test, not in production.
> **Live today:** inbound FHIR R4 ingest, patient FHIR/PDF export, Health ID
> + QR record verification, partner API with webhooks (SSRF-hardened),
> sandbox. **On the roadmap:** outbound FHIR server, HL7 v2, PACS.
> **For your institution, the six benefits:** 1 patient-one record · no
> re-typing · billing completeness · referral continuity · gradual adoption
> (one system or all twenty-two) · Cameroon-law-aligned privacy posture.
> **Next step: a 15-minute call with your IT + clinical leads.**
> Opesware · opesware.com/health · opescare.com · contact@opesware.com ·
> +237 670 416 238

---

# PART II — LinkedIn post pack (ready-to-publish, EN + FR pairs)

Cadence per MARKETING_PLAN Phase 1: 2–3 posts/week rotating four pillars.
Structure: hook → one platform fact (playbook §2) → honesty line → CTA.
Never superlatives, never unverifiable numbers, screenshots from demo host only.

### Post 1 — the honesty signature (pillar: product truth)
EN: "Most health apps in Cameroon show you a pharmacy that looks stocked.
We refuse to. If no pharmacy has reported a medicine's stock, OpesCare Finder
says so — 'no pharmacy reported' — instead of selling you an empty promise.
Availability is sourced or it isn't shown. That's the whole product
philosophy. opescare.com #Cameroon #HealthTech"
FR: « La plupart des applis santé au Cameroun vous montrent une pharmacie qui
semble approvisionnée. Nous refusons. Si aucune pharmacie n'a déclaré de
stock, OpesCare Finder le dit — plutôt que de vous vendre une promesse vide.
La disponibilité est sourcée ou elle n'est pas affichée. C'est toute la
philosophie du produit. opescare.com #Cameroun #HealthTech »

### Post 2 — the interoperability layer (pillar: ecosystem)
EN: "A lab result that never reaches the doctor who ordered it. A prescription
re-typed at the pharmacy. A patient who exists three times in your own
system. Twenty-two OPES health systems now share one answer: one Health ID,
issued once, verified by QR, and every system connects through it — FHIR R4
today, more on the roadmap. Start with one system, or start with all of
them. opesware.com/health #DigitalHealth #Interoperability"
FR: mirror translation (translate fully, never post half).

### Post 3 — blood finder (pillar: life-saving utility)
EN: "If someone you love needs O− tonight, do you know which blood bank has
it? Blood banks on OpesCare publish inventory that lands in a public,
searchable finder — provenance-tracked, refreshed by the bank itself, with a
request desk behind it. If your bank hasn't joined yet: it's one table to
fill. opescare.com #Cameroon"
FR: mirror.

### Post 4 — pharmacy supply loop (pillar: supply unlock)
EN: "Patients search 'is [medicine] available near me?' every day. Every
pharmacy that publishes its shelf on OpesCare becomes the answer to that
question — with expiry tracking, batch movements and counter records behind
it. Your stock desk is free during early adoption; entering five
fast-moving molecules takes twenty minutes. Douala and Yaoundé pharmacies:
claim your listing. opescare.com"
FR: mirror.

### Post 5 — Health ID (pillar: identity)
EN: "One patient. One number. One scan. The OPESCARD QR is a verification
link — it proves identity without leaking it: name and details stay behind
consent, the audit trail stays visible, and guardianship is handled for
minors. Health identity is a safety mechanism, not a loyalty card. How it's
built: opescare.com #HealthID #Cameroon"
FR: mirror.

### Post 6 — students & workforce (pillar: community)
EN: "Cameroon doesn't lack clinical talent; it lacks health IT fluency. We
turn nursing and lab students into OpesCare-certified implementation
officers — free training, supervised practice on real screens (read-only,
demo data), and a certification they can put on their CV. First cohorts are
openning. Students & schools: DM us. #HealthIT #Training"
FR: mirror.

### Post 7 — direcctor lessons (pillar: honest build)
EN: "Building health software for Cameroon means designing for the elevator
pitch you can't make: no shared EHR, loose identities, paper results. We
started from the hard cases — consent-gated records, emergency access with
patient notification, encrypted offline mode for patients. The boring
infrastructure IS the product. What would you want a Cameroonian EHR to
guarantee first? #HealthTech"

### Post 8 — directory claims (pillar: growth asset)
EN: "2,728+ facilities are already in the national directory on
opescare.com. If yours is one of them, claim your listing free: correct
hours, services and contacts, verified badge path — the first thing patients
search for. Unverified claims stay unverified on purpose: trust must be
earned line by line."
FR: mirror.

**Publishing checklist before ANY post**: playbook §11 (claim check, FR pair
complete, screenshot from demo host, currency FCFA, CTA = opescare.com public
URL). Log post + link in the weekly KPI sheet.

---

# PART III — Grants research & application program

## Purpose
Fund the rollout (Phase 3–4 of MARKETING_PLAN) without waiting for paid
revenue. Hermes = research & drafting; **the owner approves and signs every
application** (financial figures, legal entity commitments).

## Where to search (verified sources; re-verify + bookmark weekly)
| Category | Concrete programs to check first |
|---|---|
| Grand-challenge style | Grand Challenges Africa / Grand Challenges Canada seed awards; Gates Foundation Grand Challenges rounds |
| DFIs / development banks | African Development Bank (youth & digital economy windows), World Bank / IFC digital health programs, AFD (French bilateral: health & digital windows, CEMAC-relevant) |
| EU multilateral | EU Horizon Europe & Global Gateway digital-health calls (ACOAST partnership angle), EIB |
| Global health vehicles | WHO/HIS & digital health programmatic work, UNICEF Giga-type digital access funds, DIAL/PATH digital health forums, Digital Health Atlas listings |
| Philanthropy | Google.org Africa AI/health, Rockefeller Foundation, Skoll (later stage), Mutituner (later stage), The African Development Foundation (USADF) |
| Telco CSR / local | MTN Foundation Cameroon, Orange Foundation, MAETUR/digital-mission programs, national digitalization agendas (Cameroon National Development Strategy / digital sector strategy 2030) |
| Accelerators (non-equity or friendly) | Y Combinator (already targeted), Techstars / Norrsken / Villgro Africa, Founders Factory Africa |

## Qualification filter (Hermes triages before involving anyone)
1. Is health OR digital infrastructure in scope for this round? 2. Are we
eligible (stage: pre-revenue product deployed; geography: Cameroon/CEMAC;
entity type: Opesware)? 3. Ticket size & timing vs rollout needs (backups to
move off-disk, email/DNS fix, merchant rails, iOS build — the plan's known
gaps)? 4. Strings attached (exclusivity, IP, data residency) vs our honesty
model. **If any answer is no, log it and move on** — applications are
expensive to us in time.

## The evidence library Hermes assembles (attach-ready, all verifiable)
- Playbook §2 facts table (2,728-listing national directory, 4 finders,
  booking/queue, Health ID/MPI/consent, FHIR R4 ingest, mobile app on
  Google Play 1.5.0, DHIS2 aggregate reporting pipeline, provenance honesty
  machinery).
- Impact framing (use with care — measured only): "first stock reported /
  first blood unit surfaced / first facility at readiness threshold" once
  they happen; **never fabricate impact metrics**.
- Security/compliance posture: deep-dive §4, SECURITY_REMEDIATION.md-style
  historian, Law 2010/012 modelling.
- Product/roadmap maturity: deep-dive §5 (LIVE/BACKEND/ROADMAP table) —
  demonstrates disciplined scope honesty.
- Budget asks that map to real gaps: off-disk backups (S3), merchant rails
  (MoMo/Orange), SMS gateway, iOS build, national-scale hosting, student
  program cohorts (STUDENTS_PROGRAM §5).

## Application workflow
1. Hermes drafts: 1-pager (problem, solution, traction-to-date, honest gaps),
   5-slide deck from the proposal pack + one pilot case, budget table draft
   with owner. 2. Owner reviews/signs. 3. Submission logged in
   `docs/GRANTS_RESEARCH.md` (funder; round; deadline; ask; entity; outcome;
   next action). 4. Reporting duties tracked (esp. any grant that funds
   go-live metrics — ensure MetricsSnapshot evidence lines exist).

---

# PART IV — OPES solutions: opesware.com/health catalog & migration to opescare.com

## What the catalog says (verbatim facts Hermes may cite)
- **"Twenty-two systems. One patient record."** Healthcare software built for
  Cameroon and the CEMAC region, from single-room clinic to multi-department
  referral hospital. **Every system shares one Health ID**, so "a patient
  stays one patient wherever they are treated".
- Ecosystem designed to be **adopted gradually** — "deploy one system or the
  whole set".
- **Core platform**: OPESCare (Health ID & Interoperability Layer) · OPES EMR ·
  OPES Hospital HIS · UHC IS (Universal Health Coverage Information System).
- **Diagnostics**: OPES Lab (LIS) · RADIS (radiology) · PHARMIS (pharmacy) ·
  Opes Triage (standalone triage).
- **Specialist systems (12)**: CARDIS (cardiology) · DENTIS (dental) · DERMIS
  (dermatology) · ENDOIS (endocrinology & diabetes) · GYNOBSIS
  (obstetrics & gynaecology) · MHIS (mental health) · NDIS (nutrition &
  dietetics) · OPHIS (ophthalmology) · ORTHOIS (orthotics & prosthetics) ·
  PAEDIS (paediatrics) · REHABIS (physical medicine & rehabilitation) ·
  SLTIS (speech & language therapy).
- **Administration & finance**: OPES CDMS (clinical documents) · RCMIS
  (revenue cycle management).
- Contact for catalog/products: Opesware (Petite Terrain, Bonamoussadi,
  Douala), contact/partners/projects @opesware.com, +237 670 416 238.

## Migration posture — how Hermes talks about "moving them to opescare"
1. **What is already live on opescare.com today**: the OPESCare core in
   production — Health ID & OPESCARD, the national health-finder (medicine,
   blood, appointments, directory), facility operational desks (pharmacy,
   lab/imaging, queue/triage, billing, referrals), consent/records, FHIR R4
   ingest, partner API — i.e., the ecosystem's "one Health ID" layer, live.
2. **What remains catalog-terms**: the 22 named systems (EMR, HIS, specialist
   systems, CDMS/RCMIS/UHC) are presented on opesware.com/health while
   consolidation into the opescare.com experience proceeds. Describe
   migration as a program ("OPES systems are converging on opescare.com") —
   **never** tell a prospect their specialist system is already inside the
   live site if it isn't.
3. **"Look up your own system" rule**: for each prospect, Hermes names the
   specific OPES system that matches THEIR facility (dental → DENTIS;
   ophthalmology → OPHIS etc.) via the catalog, then offers the live
   opescare.com desks that serve the same need today, plus the migration
   path ("the Health ID you'll carry in the consolidated platform is the one
   your listing/desks already use").
4. **Two-site hygiene**: opesware.com/health is the *product catalog +
   company site* through migration; opescare.com is the *live platform*.
   Public posts link catalog pages for product curriculum (22 systems) and
   opescare.com for live experience. Never redric the catalog content
   (don't copy it into posts); link it.
5. **Claims**: approval to be caring is within existing claims-contract —
   "OPESCare Health ID is live on opescare.com (playbook §2)", while
   features named only in catalog pages (e.g., RCMIS functionality, UHC IS)
   are NOT claimed as live features on opescare.com.
