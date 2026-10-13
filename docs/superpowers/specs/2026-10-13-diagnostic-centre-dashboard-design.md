# Diagnostic centre dashboard — complete desk (design, 2026-10-13)

A diagnostic centre = laboratory + imaging under one roof (`facility_type` is
`diagnostic_center`; `imaging_center` and `laboratory` are the single-service cases).
The lab half already has a desk (`/facility/lab*`). The imaging half has a table
(`imaging_orders`), a report service (`RadiologyReportService`: createDraft / finalize
/ amend / distribute) and nothing else: **no intake, no worklist, no report desk, no
role**. This design adds the imaging desk, a centre landing, and the publication of
imaging procedures, so a centre can be run end-to-end from the web.

Yardstick: `docs/DASHBOARD_SCOPE.md`. Audit items closed by this design: I-3, I-4,
I-5, I-6, I-7, D-6, D-8. D-7 (bundle pricing) stays an owner decision (schema).

## Roles and capabilities (config/staff_capabilities.php)
- New capability `imaging` (worklist, intake, scheduling, completion) → roles
  `imaging_tech` (new), `radiologist` (new), `lab_manager`, `labtech`, `facility_admin`
  at imaging/diagnostic facilities, `doctor` (intake only, via referral).
- New capability `imaging_report` (write/finalize/distribute reports) → `radiologist`,
  `lab_manager`.
- New sidebars `partials/sidebars/imaging_tech.blade.php`, `radiologist.blade.php`:
  Imaging dashboard, Worklist, New request, Reports, Messages, Tasks, Support — **no**
  blood, **no** sample collection.
- `StaffRoleMatrixTest` gains rows for both roles.

## Information architecture
- `/facility/imaging` — worklist (tabs: pending, scheduled, in progress, completed
  awaiting report, reported; filters: modality, urgency, date)
- `/facility/imaging/orders/new` — intake (walk-in or referral): patient by exact
  Health ID (same lookup rule as triage), modality (xray/ultrasound/ct/mri/mammography/
  other), body part, clinical indication, urgency, referring physician/facility,
  scheduled slot; creates `ImagingOrder` with an accession number
- `/facility/imaging/orders/{id}` — detail: patient (age band, never DOB), order
  facts, status timeline, actions by status (schedule → start → complete → cancel with
  reason), report panel (draft/final/amended, PDF attachment, distribute button),
  access audited via `PortalContextService::auditPatientAccess`
- `/facility/imaging/orders/{id}/report` — report desk (radiologist): findings,
  impression, optional PDF upload (`FileStorageService`, private disk), finalize,
  amend (new version, old kept), distribute → patient results inbox + notification;
  patient sees it under documents as an `IMAGING_REPORT` secure-sheet PDF with the
  document QR, exactly like `LABORATORY_REPORT`
- `/facility/diagnostic` — centre landing (lab_manager / facility_admin at a
  diagnostic or imaging facility; lab-only facilities keep `/facility/lab/dashboard`):
  KPIs for both halves (lab open / resulted today / turnaround; imaging pending /
  today / awaiting report / reported), publication panel split lab vs imaging
  (`ServiceMix`), empty states that say what to do next
- Admin overview at imaging/diagnostic facilities hides Beds and Wards (I-7)

## Publication (I-3)
`LabCatalogueProjector` publishes ICHI imaging procedures to the imaging finder
(`ImagingExamSearch`) the same way LOINC tests go to the lab finder; the catalogue page
shows which finder each row is on. Provenance rules unchanged: only `source_portal`
rows from a linked listing, nothing seeded.

## Cross-cutting
Route + sidebar + breadcrumb + feature test per page (200 right role, 403 wrong role,
404 cross-facility). Strings in `lang/{en,fr}/imaging_desk.php`. Nonce CSP, no inline
handlers, no new inline styles, Lucide only, PostgreSQL, typed status via `match`.
Controllers thin; logic in `app/Services/Imaging/ImagingDeskService.php`.
