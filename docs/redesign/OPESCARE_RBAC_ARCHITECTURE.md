# OpesCare RBAC & Multi-Facility Architecture

**Status:** Authoritative design — 2026-06-14
**Audience:** Engineering + Ministry presentation reference

This document defines *who* can access *what* in OpesCare, grounded in the
scaffolding that already exists in the codebase (we extend it, we do not rebuild it).

---

## 1. The core idea: two planes

OpesCare has **two completely separate planes of access**. Conflating them is the
single biggest current defect ("I log in as a clinic and become super admin").

| Plane | Who | Scope of data | Portal |
|-------|-----|---------------|--------|
| **Platform plane** | OpesCare (the company that operates the platform) | ALL facilities, ALL patients — god-mode | `/portals/admin/*` (Control Center, Security Ops, Subscriptions, KPI, Onboarding, Legal, Certifications, Bridge, Developer governance) |
| **Facility plane** | A customer organisation (a hospital, clinic, pharmacy, lab, insurer, NGO, dev partner) and its staff | ONLY that facility's data | `/portals/{staff,admin-facility,pharmacy,lab,insurance,healthorg,developer}` scoped to `active_facility_id` |

**Rule:** A facility user must *never* see another facility's data, and must
*never* see platform-plane (god-mode) screens. Platform users operate the
business of OpesCare itself.

Enforced by three middleware that already exist:
- `EnsurePortalAccess` — role → which portal.
- `RequirePlatformAdmin` — platform-only paths → platform roles only (403 otherwise).
- `RequireFacilityContext` — resolves/locks the `active_facility_id` for facility users; platform roles bypass.

---

## 2. Facility types

Stored as `facilities.type` (free string today; we standardise the vocabulary).
A facility (a.k.a. organisation) is one of:

| `type` | Description | Demo org name |
|--------|-------------|---------------|
| `hospital` | District / regional / general hospital (multi-department, wards, lab, pharmacy in-house) | **OpesHospital** |
| `clinic` | Private clinic / integrated health centre | **OpesClinic** |
| `pharmacy` | Standalone / chain pharmacy | **OpesPharmacy** |
| `laboratory` | Standalone diagnostic laboratory | **OpesLab** |
| `insurance` | Insurer / mutuelle de santé | **OpesInsurance** |
| `health_org` | NGO / public-health programme / mobile outreach | **OpesHealthOrg** |
| `developer` | API partner / integration company | **OpesDeveloper** |
| `lite` | Low-connectivity facility on OpesCare Lite | **OpesLite** |
| `platform` | OpesCare itself (not a customer) | **OpesCare Platform** |

---

## 3. Module entitlement matrix (facility type → modules)

Modules are gated by `EnforceModuleEntitlement` (`module:` middleware) and stored
per-organisation in `module_entitlements` (granted via subscription). Module
availability is **not hard-wired to type** in code — type sets the *default*
bundle a facility is provisioned with; the platform can grant/revoke per org.

Legend: ● default-on · ○ optional add-on · — not applicable

| Module | hospital | clinic | pharmacy | laboratory | insurance | health_org | developer | lite |
|--------|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Patient registry / Health ID | ● | ● | ● | ● | — | ● | — | ● |
| Appointments & queue | ● | ● | ○ | ○ | — | ○ | — | ● |
| Visits / EMR / clinical notes | ● | ● | — | — | — | ○ | — | ● |
| Wards & admissions | ● | ○ | — | — | — | — | — | — |
| Laboratory orders & results | ● | ○ | — | ● | — | — | — | ○ |
| Pharmacy & dispensing | ● | ○ | ● | — | — | — | — | ○ |
| Inventory / supply chain | ● | ○ | ● | ○ | — | ○ | — | — |
| Billing & payments (XAF) | ● | ● | ● | ● | — | ○ | — | ● |
| Insurance claims & pre-auth | ○ | ○ | — | — | ● | — | — | — |
| Telemedicine | ○ | ○ | — | — | — | ○ | — | — |
| CDSS (clinical decision support) | ● | ● | ○ | ○ | — | — | — | — |
| Referrals | ● | ● | — | ○ | — | ○ | — | ○ |
| Immunisations | ● | ● | — | — | — | ● | — | ● |
| Public-health reporting (MINSANTE/DHIS2) | ● | ○ | — | ○ | — | ● | — | — |
| Analytics / KPI | ● | ● | ○ | ○ | ○ | ● | ○ | — |
| Data import | ● | ● | ○ | ○ | ○ | ○ | — | — |
| Bridge / device integration | ○ | ○ | ○ | ○ | — | — | ○ | ● |
| Developer SDK / API / webhooks | — | — | — | — | ○ | — | ● | — |

---

## 4. Roles within a facility type (who sees what)

Each facility type provisions a subset of the 107 seeded roles. Within a
facility, **not all users are equal** — privileges differ by role. The role a
user holds *at a given facility* comes from `facility_role_assignments`
(`user_id × facility_id × role_id`), resolved by `User::roleAtFacility()`.

### hospital (OpesHospital)
| Role | Can see / do | Cannot |
|------|--------------|--------|
| `hospital_admin` | Facility dashboard, its staff, its patients, its finance, its settings (scoped to this hospital) | Other facilities; platform god-mode |
| `doctor` / `specialist` | Their patients' EMR, orders, prescriptions, notes, referrals, telemedicine | Billing admin, HR, other doctors' unrelated panels |
| `nurse` / `ward_nurse` / `triage_nurse` | Triage, vitals, ward care, medication administration | Prescribing, billing config |
| `receptionist` / `front_desk` | Registration, appointments, queue, check-in | Clinical notes, prescriptions |
| `labtech` / `lab_manager` | Lab orders, samples, results | EMR notes, prescribing |
| `pharmacist` / `pharmacy_manager` | Dispensing, drug inventory, controlled register | Diagnoses, lab |
| `cashier` / `billing_officer` / `finance_manager` | Invoices, payments, refunds (scoped) | Clinical data beyond billing context |
| `records_officer` / `data_steward` | Records, data import, reconciliation | Clinical authoring |

### clinic (OpesClinic)
Same role family as hospital, typically without wards/admissions and with a
leaner staff set: `clinic_admin`, `doctor`, `nurse`, `receptionist`, `cashier`,
optional `labtech`/`pharmacist`.

### pharmacy (OpesPharmacy)
`pharmacy_manager` (facility admin), `pharmacist`, `pharmacy_technician`,
`dispensing_officer`, `medicine_stock`, `cashier`. Portal: **/portals/pharmacy** + facility billing.

### laboratory (OpesLab)
`lab_manager` (facility admin), `lab_scientist`, `labtech`, `lab_validator`,
`sample_collection`, `cashier`. Portal: **/portals/lab**.

### insurance (OpesInsurance)
`insurance_admin` (facility admin), `insurance_reviewer`, `insurance_claims`,
`insurance_preauth`, `insurance_finance`. Portal: **/portals/insurance**.

### health_org (OpesHealthOrg)
`ngo_admin` (facility admin), `health_program_manager`, `outreach_team`,
`mobile_clinic_team`. Portal: **/portals/healthorg**.

### developer (OpesDeveloper)
`developer_org_admin` (facility admin), `developer`, `api_partner`,
`api_technical`, `webhook_manager`, `sandbox_developer`. Portal: **/portals/developer**.

### lite (OpesLite)
`lite_facility` (admin), `lite_staff`, `lite_device`, `lite_offline_sync`. Portal: **/portals/lite**.

### platform (OpesCare itself — NOT a customer)
`super_admin`, `platform_admin`, `system_admin`, `product_admin`,
`country_admin`, `regional_admin`, plus platform-company functions
(`security_officer`, `compliance_officer`, `support_agent`, `partner_admin`,
…). Portal: **/portals/admin/** god-mode.

---

## 5. How a doctor logs into a facility (the multi-facility question)

**Model: one human identity, many facility memberships.** This is the
industry-standard pattern (Epic "login department", Cerner position/encounter
context) and is already supported by `facility_role_assignments`.

1. **One account per person.** A doctor registers/credentials **once** — one
   email + password tied to their person and professional licence (verified via
   the credentialing flow). They do **not** get a separate login per hospital.

2. **Each facility grants them a membership.** When a hospital employs the
   doctor, that hospital's admin creates a `facility_role_assignment`:
   `(this doctor) × (this hospital) × (doctor role)`. A doctor working at three
   hospitals has three assignments — possibly with *different roles* (e.g.
   `consultant` at one, `visiting_doctor` at another).

3. **At login they choose the facility for this session.** After
   authenticating:
   - If they belong to exactly **one** facility → it is auto-selected
     (`active_facility_id` set from the single membership) and they go straight in.
   - If they belong to **multiple** facilities → they hit the **facility
     selector** (`/select-facility`) and pick "who am I working for right now."
     This establishes the active clinical session.
   - Platform users have **no** facility and bypass the selector.

4. **Privileges follow the chosen facility.** For the rest of the session,
   `roleAtFacility(active_facility_id)` determines their role, their portal, and
   the data they can touch — strictly scoped to that one facility. Switching
   facility = re-selecting (a "switch facility" action), which re-scopes everything.

> So: **not** a separate password per hospital. One identity; the facility grants
> a role; the doctor selects the active facility at login; the platform enforces
> that they only ever act and see within that facility.

**Gap to close (tracked):** `/select-facility` currently lists only the user's
`primary_facility_id`. To fully support the multi-facility doctor it must list
**all** facilities where the user has an active `facility_role_assignment`.

---

## 6. Enforcement layers (defence in depth)

1. **Authentication** — `Auth::attempt`, account status (active/pending/suspended).
2. **Portal routing** — `EnsurePortalAccess`: role → allowed portal; wrong portal → redirect to the user's own.
3. **Platform isolation** — `RequirePlatformAdmin`: platform-only paths → platform roles only, else 403.
4. **Facility context** — `RequireFacilityContext`: facility users must have an `active_facility_id`; platform users bypass.
5. **Module entitlement** — `EnforceModuleEntitlement`: feature gated by the org's subscription.
6. **Data scoping** — controllers/queries filtered by `active_facility_id` (the area needing the most hardening: facility-plane admin controllers must scope, not god-mode).
7. **Per-facility role** — `roleAtFacility()` is the source of truth for the active session's privileges.

---

## 7. Required fixes (this is what makes it "real")

- [ ] **Distinct facility-admin sidebar** — `partials/sidebars/facility_admin.blade.php`
  must show ONLY facility-scoped links (no Control Center, Security Ops,
  Subscriptions, Plans, KPI-platform, Legal, Certifications, Code Mappings,
  Connect, Bridge, Developer governance, God-Mode Data, Onboarding, Reports).
  Today it is a copy of `super_admin.blade.php` — the cause of the leak.
- [ ] **Comprehensive platform-only gate** — add every god-mode/platform path
  (incl. `onboarding`) to `RequirePlatformAdmin::PLATFORM_ONLY_PREFIXES` so a
  facility admin gets 403 even by typing the URL.
- [ ] **Facility-plane data scoping** — facility-admin controllers filter by
  `active_facility_id` (follow-up; biggest remaining work).
- [ ] **Multi-facility selector** — list all `facility_role_assignments`, not just primary.
- [ ] **Demo orgs + role accounts** — one `opes{Type}` org per facility type,
  with one account per applicable role, all testable.
