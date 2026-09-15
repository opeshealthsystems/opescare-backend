# Clinic dashboard — audit 2 (2026-09-15)

Scope: a CLINIC (small outpatient facility, 916 in the directory): 1–2 doctors,
walk-ins, no wards, usually no own lab or pharmacy. Demo facility "Clinique Demo
Bonapriso"; logins demo.clinic.{admin,doctor,nurse,reception}@opescare.test.
Yardstick: docs/DASHBOARD_SCOPE.md. Prod checked GET-only on
finder.opescare.cloud (form login, cookie jar); fixes tested locally.

## Plan (ranked)

| # | Item | Owner | Status |
|---|---|---|---|
| C-1 | Prescriber desk fell back to `Facility::value('id')` (first facility in the table) for an account with no posting — cross-facility list/write | clinic auditor | **FIXED** (DeskFacilityContext + named 403) |
| C-2 | RX code (what the pharmacy counter looks up) shown only on the printed sheet — not on the desk, not in the patient portal; a patient without the paper cannot fill at another pharmacy | clinic auditor | **FIXED** (desk show + index + patient portal) |
| C-3 | No web referral desk for clinic staff — referring out and referral status exist only on the clinician mobile API (`routes/mobile_doctor_referrals.php`); patient reads at `/patient/referrals` | lead (routes/web.php + doctor sidebar) | OPEN — shared-file change |
| C-4 | Patient not notified when a prescription is issued (PrescriptionService::issue writes audit only) | developer agent (PrescriptionService) | OPEN — shared-file change |
| C-5 | clinic_admin sidebar always shows Departments and Wards & beds (hospital-shaped) | hospital auditor (sidebar/facility-admin views) | OPEN — hide for `type=clinic` or honest empty state |
| C-6 | Role refusal on clinic_admin reaching doctor/front-desk pages is a silent 302 to /admin, not a 403 naming the reason | lead (EnsurePortalAccess) | OPEN |
| C-7 | Patient prescriptions page carries `?: 'English'` fallbacks after `__()` (public.portal.*) | owner of lang/public | OPEN (cosmetic; keys exist) |

## Prod status (GET, 2026-09-15)

| Role | Page | Status |
|---|---|---|
| clinic_admin | /admin, /facility/team/overview (+ departments, invoices, reports, wards), /facility/settings, /facility/team, /facility/catalogue, /facility/queue | 200 |
| clinic_admin | /facility/appointments, /facility/triage, /facility/patients, /facility/prescriptions | 302 → /admin (not linked in sidebar; no dead link) |
| reception | /facility, /facility/queue, /facility/appointments, /waitlist, /facility/search, /facility/messages | 200 |
| reception | /facility/prescriptions | 403 (correct: no `prescribe`) |
| doctor | /facility, /facility/patients, /facility/triage, /facility/appointments, /facility/messages, /facility/prescriptions, /new, /facility/queue | 200 |
| nurse | /facility, /facility/patients, /facility/triage, /facility/queue | 200; /facility/prescriptions 403 (correct) |

Doctor prescriber list on prod: honest empty state "No prescriptions have been written here yet", facility name correct.

## Capabilities vs sidebar (clinic_admin)
`clinic_admin` ∈ `$facilityAdmins`: `queue`, `facility_team`, `service_catalogue`. Sidebar
(`partials/sidebars/facility_admin.blade.php`) gates every link on the same capability — **no 403 links**.
It does not hold `prescribe`, `clinical_chart`, `schedule`, `front_desk` — correct for an administrator;
in a 3-person clinic the admin is often also the doctor, which needs a second posting/role (owner decision, not a bug).

## Workflows
- **Reception walk-in:** queue desk + appointments + waitlist reachable (200). Writes not exercised on prod (GET-only); owned by hospital auditor (QueueDeskController).
- **Doctor:** queue → chart (`/facility/patients`, consent-gated, hospital auditor) → prescriber `/facility/prescriptions/new?patient_id=` (exact Health ID lookup, no enumeration; ClinicalRelationshipGate before issue) → show → print sheet with RX code + QR.
- **RX → any pharmacy:** `PharmacyPortalController@prescriptionLookup` accepts RX code (`RX-XXXX-XXXX`, lenient) or Health ID across ALL facilities — works. Gap was the patient never seeing the code (C-2, fixed).
- **Refer out:** API only (C-3). No web surface, no sidebar link, so no status view for a web-only clinic.
- **Patient notified:** prescription issue — no (C-4). Referral — see mobile controller (not audited further).

## Security / data
- `facility_id` from input: prescriber — none; was session → primary → first-facility fallback (C-1, fixed).
- Cross-facility: show/void `where facility_id` → 404 (test added).
- Unbounded lists: prescriber index paginates 25; create form medicine list `limit(500)` (bounded). Status filter is a plain equality on an already-scoped query.
- Immutability: void with reason via PrescriptionService, nothing deleted.

## IN / OUT of scope
- IN: prescriptions issued, RX code to pharmacy, referrals in/out, queue, team, catalogue, invoices at counter.
- OUT (DASHBOARD_SCOPE "not"): wards/beds ADT (a register only), departments as HR structure, lab instrument/QC, clinical notes.

## Removals for the owner (not done)
- `use App\Models\Facility;` in PrescriberController is now unused (left in place).
