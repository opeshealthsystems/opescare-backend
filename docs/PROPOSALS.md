# SEGMENT PROPOSALS — ready-to-send by Hermes agent
> Six formal proposal bodies. Preamble + the segment body = the sendable
> document (email attachment, LinkedIn PDF, or message). Claims are bound by
> HERMES_PLAYBOOK.md §2–§4 and the deep-dive facts. Never customize a number:
> quoting facility stats is only allowed from the playbook table.
> Last review: 2026-10-20.

## Universal preamble (all proposals)

**About OpesCare Finder**
OpesCare Finder (opescare.com) is Cameroon's bilingual (EN/FR) digital health
platform: a national health-finder for patients and a set of working digital
desks for facilities, on one shared system. A patient can find a medicine in
stock at a nearby pharmacy, locate blood by group, and book appointments —
and every availability shown is sourced (the platform never invents a stock
claim). Providers get live operational desks: reception and queue, clinical
records with consent, lab and imaging worklists, pharmacy stock and orders,
billing and referrals. Patient identity runs on a Health ID (OPESCARD) with
QR verification, and the platform ingests partner data over a FHIR R4 API.
Android app is live on Google Play (1.5.0); operation on opescare.com today
with a full one-click demo for evaluation.

**Joining is built for non-technical teams**: one-click demo logins per staff
role, a guided facility setup checklist, data import with preview and
rollback, and a go-live readiness score so a facility knows exactly when it
is ready.

**During our early-adopter phase, the facility tools and the public listing
are free. Pricing is settled individually at onboarding (FCFA) — early
adopters also get the first say in the roadmap.**

---

## 1. HOSPITALS (incl. health centres)

**Your situation**
A hospital's value flows through its front door — and that door in Cameroon
is still: paper queues, patients who cannot tell when you have stock of their
medication, results and documents moving by phone, and no longitudinal
record between visits.

**What OpesCare deploys for your hospital**
1. **Patient-front operations**: appointment booking that locks real slots,
   a waitlist that refills cancellations, a queue desk with capacity-call
   flow (check-in → start → complete → no-show), triage and a reception desk
   for walk-ins — your receptionist gets their own login; no one needs
   admin rights.
2. **Clinical continuity**: a longitudinal patient record — encounters,
   vitals, prescriptions, lab results, immunisations, documents and
   certificates, referrals in and out — protected by consent and a
   treatment-basis access model, with an access log the patient can see.
3. **Clinical worklists your unit heads already run**: lab orders,
   sample collection, results inbox; imaging worklist and reports; the
   clinician mobile experience for rounds.
4. **Medicine and blood availability made findable**: enter stock once in
   your blood/pharmacy desks and public patient search shows it, with your
   facility as the source. Provenance-tracked — the finder refuses to show
   availability you did not publish.
5. **Money**: invoices and the billing desk (FCFA), receipts, refunds —
   "care is never gated on payment" is a product principle we test for.
6. **Identity & records hygiene**: OPESCARD Health ID with QR verification,
   automatic duplicate-detection with a human-decided merge (MPI), FHIR R4
   ingest for partner/lab feeds — and every import is provenance-tagged.

**What we ask of you**
- Name one onboarding lead (no technical background needed).
- Let 2–3 staff run the 10-minute demo for one week in their own roles.
- Populate the desk you care most about first (usually reception or lab).

**Success measures we agree together**: bookings via platform, queue
time-offs, first stock visible to patients, results-turnaround after the
programme. Deployment staged against our **go-live readiness score** — you
always know when you are ready.

---

## 2. CLINICS (private, specialist, dental, eye, maternity)

Your reality: a small clinical team that wears five hats, and margins lost to
no-shows, phone-tag booking and paper folders.

What you get (all under one roof, per-role logins — reception sees
reception; the doctor sees records and booking; the nurse sees queue and
chart):
- Bookable appointments + automated reminder ladder (48 h / 24 h / 2 h)
  delivered in the patient app.
- A longitudinal patient record with consent-gated access and a visible
  audit trail (patients trust you more when they can *see* the log).
- Pharmacy stock visibility + prescriptions and dispense if you do
  in-house dispensing; lab/imaging worklists if you do in-house labs.
- Your clinic on a national public directory (2,728 listings already
  live), claimable and verifiable free, so new patients find you.
- Teleconsult booking and general referral paths are on the roadmap.
- Compliance: care never gated on payment, consent + break-glass logging,
  account deletion, retention purges — privacy posture prepared for
  Cameroon's Law 2010/012.

Fast path: claim your listing today (free) → bring reception into the
one-click demo → pilot your booking desk in your next opening week.

---

## 3. PHARMACIES (independents and chains)

**Why this matters to you**
Patients search "do you have [drug]?" — but there has been no way for them
to search *your* shelf. People drive to you for medicine they could buy
around the corner, medicine they saw a Facebook post about, or medicine a
clinic "said they have".

**What you get, specifically**
1. **A public, provenance-honest presence**: patients search the national
   medicine finder and see *exactly what you published* — nothing you didn't
   enter is ever shown. An empty summary says "no pharmacy reported",
   never a fabricated claim.
2. **The stock desk your counter already runs but in data**: batch/brouch
   labels, expiry, a movements ledger, prices with history; bulk CSV import
   (mapping wizard, preview, approve, **rollback**) and pharmacy-stock
   import jobs — you are not typing everything once.
3. **Dispensing workflow + counter sales + controlled register** and
   telephone/push prescription-filling requests from clinics on the same
   platform.
4. Free listing visibility now: your pharmacy on the national directory
   with claimed/verified badge path.
5. **FCFA integrity**: price desk, tills paid by patients pay in platform
   billing rather than promises (MoMo/Orange patient rails are on the
   rollout path; today billing is invoice-based).

**The pilot ask**: one clinic-onboarding conversation + your five fastest
turnover molecules entered today. Our team walks your stock clerk through
the desk in 20 minutes; the finder shows your shelf in the next search.

---

## 4. LABORATORIES (incl. lab benches inside clinics/hospitals)

**Your situation**: results travel by WhatsApp/phone; referral orders are
missing; patients queue for paper reports; partner HIS data dries in e-mail.

**What you get**
1. **Order-to-result digital flow**: orders arrive (reception desk or
   partner API), sample collection checklist, worklist, results entry,
   results inbox to the requesting clinician and (with consent) to the
   patient — and the mobile clinic app shows results anywhere.
2. **Records ready for import from anywhere**: CSV/lab-imaged, **FHIR R4
   ingest for DiagnosticReport and Observation** (scoped partner keys,
   per-entry consent and audit) and patient-provided imports
   (provenance-tagged, unverified-until-you-act).
3. **Terminology scaffolding so data stays comparable**: a code-mapping desk
   (mapping decisions logged), a "gaps" desk that tells your data lead
   where the code stopped resolving, no silent mapping.
4. **Lab manager dashboard** with turnaround metrics (part of our
   reporting), plus a results-turnaround discipline for clinical leads.
5. Integration posture: webhooks (SSRF-hardened), sandbox, certification
   desk — instrument/HIS integrators get a defined path.

**Pilot ask**: one week with your busiest panel; we ingest your current
sample CSV so the system starts from real data (import with preview and
rollback removes go-live risk).

---

## 5. DIAGNOSTIC CENTERS
(Combine proposals 4 and 6 — you get BOTH lab and imaging surfaces under
one centre dashboard.)

Additional imaging-specific value:
- **Imaging worklistYour booking queue for studies** — appear as an
  imaging_center on the national directory, get appointments on the finder,
  and route the study into a worklist.
- **Report desk**: technical + reporting timeline, report issue and
  revision, attachments path, patient document issue for the report copy.
- Provenance-tagged imports (PDF/image) since digital captures out of
  different machines don't run through PACS today; we do records-in-app
  instead of PACS (integration posture is defined above).

Pilot ask: your two highest-volume studies this week; we show you the
results-turnaround change after one week by reading your own worklist.

---

## 6. IMAGING CENTERS
**Your situation**: study bookings by phone (usually missed), papers moving
to the reader side, reports visible nowhere to patients/referrers.

**What you get**
1. **Worklist** (per-study inbox with priority + study-state intervals),
   study booking refill, and the full ordering trail per report from
   referrers that use the platform.
2. **Report authoring** (technical + formal timelines, one report queue,
   attachments; technical staff get the panel they need; the report owner
   (radiologist role) is separate by design.
3. **Patient-visible results path**: imaging findings flow to the patient's
   record; not an exposure of raw pixel-level studies — record-level content
   first (PACS on roadmap: say so honestly).
4. **Records-quality guardrails on report identity**: attachments validated
   on upload, signed URL documents, per-entry consent and audit inside FHIR
   ingest.

Pilot ask: connect your two highest-volume PATH of studies via the results
inbox flow and see the light of the finder referral path.

---

## 7. BLOOD BANKS (already in the campaign mix — strongest quick win)

One table (your blood inventory desk) is all it takes: blood items in
entered stock reach the PUBLIC Blood Finder instantly, as a provenance-backed
availability mark — and your team gets a blood request desk
(accept/decide/ready/fulfil) for the request/fulfilment path instead of
phones. No other channel in Cameroon currently tells patients which blood
bank has O− — you become the honest source of that information.

---

**Transport instructions to Hermes** (mechanical per send):
1. Pick ONE proposal body, keep the unified preamble exactly.
2. Translate both of pre and body pairs EN↔FR as a pair (never one half).
3. Every facility stat that appears in the body must be traceable to
   HERMES_PLAYBOOK.md §2; numbers of local market context must be
   verified in the message-thread first.
4. Attach the one-page "verified facts" sheet (playbook §2) when the
   technical reader wants proofs — never send the whole repo or doc code.
5. Close every channel with the one-click demo (opescare.com → login) and a
   named next step: call, or posting dates of a facility walkthrough.
