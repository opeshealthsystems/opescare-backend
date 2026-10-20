# OPESCARE SCHOLARS PROGRAM — student recruitment & training (Hermes agent)
> Talent-pipeline program: recruit nursing (and allied health / medical) students,
> train them in health information technology for free, certify them on the
> OpesCare systems, and give them **supervised, legally-safe access for
> practicals**. Grounded in verified platform mechanics (see "what exists" per
> item — Hermes may cite these). Claims contract from HERMES_PLAYBOOK.md still
> applies. Last review: 2026-10-20.

## 1. What already exists in the platform that makes this real (quote these)

- **Trainee roles are built in**: `student_doctor`, `student_nurse`, `intern`
  — first-class staff roles (`config/staff_capabilities.php:21`), with a
  dedicated **student portal sidebar** (graduation-cap badge,
  `partials/sidebars/student_clinical.blade.php`).
- **Supervision is enforced in code, not by policy**: trainees get the
  schedule, messaging, appointments/queue view, the **read-only** patient
  chart view and the immunisation desk view — but are **deliberately
  excluded from prescribing, acuity/triage assignment and the
  phlebotomy/dia-positive desk** (`staff_capabilities.php:73,87,113` comments).
- **Demo tenants per facility type** (hospital, clinic, lab, pharmacy,
  maternity…) with independent student-friendly logins, all stamped
  `is_demo` so training never touches real patient data
  (`docs/DEMO_ACCOUNTS.md`).
- One-click demo logins on the login page; every action lands on real
  screens (not a slide deck), in EN/FR.

## 2. The offer (what a participating student receives)

**Program name**: OpesCare Health Systems Certification — **OHSC**.

| Tier | Name | Curriculum (all free) | Practicals (on the platform, supervised) |
|---|---|---|---|
| L1 | **Health Systems Foundations** | What an EMR/HIS is; patient flow check-in→exit; Cameroon data-privacy basics (Law 2010/012 context); data quality & honesty (why the platform never shows a stock claim it cannot source); using the patient app & Health ID. | Trainee demos: patient app walkthrough, facility finder, booking flow (demo tenants). |
| L2 | **Desk Operations** *(track: queue/reception, lab, pharmacy, blood)* | Front-desk operations (reception, queue, triage-assistいつ schedule); lab track: worklists, sample collection, results discipline; pharmacy track: stock desk, expiry/movement ledger, counter records; blood track: inventory desk & request desk. | Live screens in assigned demo facility; internal "certification sandbox" tasks with a checklist; FHIR-awareness intro (what an ingest is, why provenance tags exist). |
| L3 | **Implementation Officer** | Facility onboarding pipeline; import wizard (mapping, preview, rollback); go-live readiness scoring; training a facility receptionist; consent/access-log explanation to a facility data committee. | Shadow the onboarding flow; lead one guided import in the sandbox; co-lead one facility readiness review. |

**Certification mechanics**: task-checklists executed in the demo facility +
short objective quizzes; PDF/verifiable certificate on L1–L3
(verification link pattern mirrors OPESCARD QR: identical trust pattern used
for facility badges; request it at launch). Certificates state explicitly:
**"technology-systems certification — not a clinical/professional licence."**

**What it costs the student**: nothing. **What we ask**: completion of the
track they committed to; data-privacy e-learning before any access;
supervisor sign-off on every practical (skills vs. professional licensing —
trainee roles cannot do clinical actions by design).

## 3. How a school partners (the durable channel)

1. **Letter of understanding** (owner signs; Hermes prepares EN/FR drafts):
   one facility-type demo tenant reserved for the school's cohort
   (e.g. "Hôpital Demo — École des Infirmières" branding within demo mode),
   named cohort of trainee seats, OHSC cert issuance list per cohort.
2. **Faculty-of-record**: one school staff member as cohort supervisor with
   facility-admin demo visibility to monitor student activity.
3. **Practical placement note for real facilities** (Phase 3 only): when a
   real facility comes on board, its own student/intern seats follow facility
   policy — never auto-granted real-patient access by the program.

## 4. Recruitment (where Hermes finds students and what it says)

**Channels (in priority order)**
1. LinkedIn: nursing/lab-school cohorts, class-group pages, student association
   posts (join + participate before pitching — no login-outreach cold blasts;
   platform ToU).
2. WhatsApp student groups (forwarded from one student to the group; the
   pitch offers the free training, never "payments").
3. School notice boards via the partnership letter: one-page bilingual
   poster (EN/FR) with the OHSC value and QR to the login demo.
4. Referral engine: every certified student gets share credits (tracked in
   leads desk, `source=student_referral`).

**Recruitment message (FR primary, EN mirror)**

> FR: « Vous studiez医療 comme infirmier(ère) / technicien de labo / sage-femme
> ? OpesCare offre une formation gratuite en **santé informatique** (systèmes
> d'information hospitaliers, données patient, confidentialité) avec la
> certification OpesCare Health Systems — et vous vous entraînez sur de vraies
> interfaces de l'hôpital (modes démo). Google Android app + site opescare.com.
> Places par cohorte : [N]. Écrivez-nous [contact] pour la prochaine cohorte. »

Rules for Hermes outreach to students:
- Verify enrollment before certifying messaging to a specific person? — No:
  verify at certification; recruitment is open and filter by echo-reply.
- **Never** imply clinical employment, paid clinical duties, or guaranteed
  jobs: earning is limited to (a) the free training/certification and
  (b) *priority consideration for paid implementation-officer engagements
  and tech-support roles as facilities onboard* (conditional, owner-decided).
- Zero claims that OHSC grants any clinical licence or professional
  qualification (deliberate in certificate text).
- Students never get real patient data through the program: platform code
  (trainee read-only chart, demo stamps) enforces this; repeat it in every
  welcome message.

## 5. Program KPIs (weekly, alongside the main marketing plan)

| Metric | Source | 90-day target |
|---|---|---|
| Cohorts signed (schools) | LOUs | 2 |
| Students onboarded (L1) | leads desk `source=student_*` / cohort lists | 150 |
| L1→L2 progression | OHSC checklist tracker | 40% |
| L3 certified (implementation officers ready) | certification list | 8–12 |
| Student-referred facility leads created | leads desk | +10 (they recruit pharmacies/blood banks — students know their area's clinics) |
| Facility real-world practicals (Phase 3+) | onboarding notes | first 2 placements |

## 6. Why this is strategically important (owner context)

- It **manufactures the workforce the supply loop needs**: implementation officers
  from the students program are the hands that sit with a facility's reception
  or stock clerk at go-live (Phase-2/3 of the main MARKETING_PLAN.md).
- It positions OpesCare as a **national training brand**, not a vendor —
  the compliance story grows every student who has read Law 2010/012 basics
  on our platform.
- It is the cheapest channel with the highest goodwill: free training +
  certification + supervised access, zero pay ImportError – and the platform's
  existing trainee roles (student_doctor / student_nurse / intern) mean the
  program is a configuration of something already built, not a new product.

## 7. Program risks & guardrails

| Risk | Guardrail |
|---|---|
| "Certificates = licence" misconception | Explicit certificate text; facility committees briefed; no clinical duty language anywhere |
| Student reaching real patient data | Program runs on demo tenants; real facility seats only under facility policy; trainee roles are read-only on charts by platform code |
| Over-promising earnings | Public copy limited to free training + certification + *priority consideration* statement; engagement pay decided per-engagement by the owner |
| Outreach etiquette (student spam) | Playbook §11 hygiene; 1 msg/week/recipient; group-first (cohort pages), person-first only after organic contact |
| Regulatory/education-scope questions | Route to owner; Education ministry context handled at partnership level |
