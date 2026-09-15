# Hospital dashboard — audit pass 2 (2026-09-15)

Scope: a hospital as an institution on the Finder. Admin tier
(demo.hospital.admin / demo.facility.admin / demo.facility.ceo) and the
operations desk at Hôpital Central de Yaoundé (demo.doctor / nurse /
receptionist / labtech / pharmacist). Yardstick: `docs/DASHBOARD_SCOPE.md`.
Prod walked GET-only with a curl cookie jar after form login (finder.opescare.cloud,
2026-09-15). Cross-facility behaviour tested locally.

## Plan (ranked: bug > broken workflow > missing detail > inconsistency > polish)

| # | Rank | Item | Status |
|---|---|---|---|
| 1 | bug | Web confirm rewrote ANY status (completed/cancelled) to `confirmed` | FIXED |
| 2 | bug | Web cancel of a foreign-facility appointment answered 302 + raw "No query results" (findOrFail inside `catch (Throwable)`) instead of 404 | FIXED |
| 3 | broken workflow | Patient's own booking (`booked`) had no Confirm / No-show / Cancel buttons on the web table (it knew only `scheduled`) | FIXED |
| 4 | broken workflow | Two check-in doors: web check-in used `AppointmentService::checkIn` (scheduled/confirmed only, no queue ticket, raw exception text); queue desk used `PatientQueueService` (ticket + visit). Web now uses the queue desk's door; button offered only to roles holding `queue` | FIXED |
| 5 | bug | Queue / triage / chart desks (no `facility.context` on their route files) fell back to `primary_facility_id`: no primary → 403 despite a real posting; two postings → silently pinned. Now `DeskFacilityContext` applies RequireFacilityContext's rule | FIXED |
| 6 | inconsistency | Appointment book: clinician column printed raw provider uuid, facility column raw facility uuid, filter was a free-text "Provider ID" box, no status filter although controller honoured `?status=` | FIXED |
| 7 | unbounded / lying list | Appointment book `limit(100)` silently dropped the rest of the book (no page 2) | FIXED (paginate 50) |
| 8 | i18n | Hard-coded English: 409 "No facility is selected…" message, Health-ID placeholder, search-type headings (`ucfirst($type)`) | FIXED |
| 9 | inconsistency | No-show had no audit row; confirm/cancel did | FIXED |
| 10 | URL-only | `facility_executive` (CEO) opens `/facility/queue` (200) but its sidebar has no queue link | SHARED (sidebar / capability) |
| 11 | inconsistency | Hospital admin landing `/admin` is titled "Admin Governance Portal"; the facility overview lives at `/facility/team/overview` | SHARED (AdminPortalController / sidebar) — owner decision on which is the landing |
| 12 | inconsistency | Role refusal on `/facility/appointments` for an admin is a 302 to `/admin` (EnsurePortalAccess), not a 403 naming the reason, while `/facility/triage` for a receptionist is a 403 | SHARED (EnsurePortalAccess) |
| 13 | missing link | Doctor sidebar has no lab link; `/facility/lab` is 403 for a doctor (lab orders are raised from the chart / prescriber path). Doctor has no `referrals` web page at all on `/facility*` | OWNER decision |
| 14 | bounded list | Waitlist `limit(200)` with no "more" indicator | OPEN (polish) |
| 15 | test gap | `/facility/prescriptions/{id}` foreign id: code-scoped (`where facility_id`) but no local test; file is being edited by another agent | OPEN (lead) |

## Prod walk (2026-09-15)

| Account | Path | Status |
|---|---|---|
| receptionist | /facility, /facility/appointments, /create, /waitlist, /facility/queue, /queue/check-in, /messages, /search, /blood-requests, /blood-requests/new, /inventory/blood, /facility/billing, /billing/new, /billing/refunds, /billing/sessions | all 200 |
| receptionist | /facility/triage, /facility/patients | 403 (no capability — correct) |
| doctor | /facility, appointments, waitlist, queue, triage, triage/new, patients, prescriptions, prescriptions/new, blood-requests, inventory/blood, messages, search, search?q= | all 200 |
| doctor | /facility/triage/{id} ×2, /facility/prescriptions/{id} ×2 (from list links) | 200 |
| doctor | /facility/lab | 403 (no sidebar link — consistent) |
| nurse | /facility, queue, triage, triage/new, patients, appointments | all 200 |
| labtech | /facility, /facility/lab, /lab/collection, /lab/dashboard, /lab/requests, /lab/orders/{id} ×2, messages | all 200 |
| hospital.admin | /admin, /facility/team/overview (+invoices, departments, reports, wards), /facility/team, /facility/settings, /facility/catalogue, /catalogue/browse, /facility/queue | all 200 |
| hospital.admin | /facility/appointments | 302 → /admin (#12) |
| facility.ceo | /admin, overview, team, settings, catalogue | 200; /facility/queue 200 without nav (#10) |

Queue, blood-request and message lists were empty for the demo doctor on prod
today, so their detail pages were verified locally only (existing
`PatientQueueDeskTest`, `BloodDeskWebTest`, `TriageDeskTest` foreign-id tests).
Platform-admin pages (MFA) not walked — covered by local feature tests.

## Workflows

- **Appointment → confirm → check-in → queue → triage → chart → prescription → dispense:**
  was broken at confirm/check-in for app bookings (#1, #3, #4). Now end-to-end:
  `HospitalAppointmentDeskTest::test_a_booked_request_from_the_app_can_be_confirmed_and_checked_in_from_the_web`
  asserts `checked_in`, visit opened, ticket `waiting` at the same facility.
  Triage/chart resolve the same facility (#5). Dispense is the pharmacy audit's.
- **Lab order → collect → result → validate → release:** lab desk pages 200 on
  prod; owned by the lab audit (`docs/LAB_DASHBOARD_AUDIT.md`).
- **Blood request → decide / message thread:** pages 200; lists empty on prod;
  covered by Blood* tests. Not changed here.

## Facility resolution
`facility_id` never read from input in the five owned controllers (grep of
`$request->facility_id`, `input('facility_id')`: none). Desks now share
`App\Services\Portal\DeskFacilityContext` (session → one posting → selector → 403).
Note: `FacilityMembershipService::facilityIdsFor` counts `primary_facility_id`
as a posting, so primary + one other posting = the selector.

## Out of scope (DASHBOARD_SCOPE "not" list) — reported, not built
- Ward/bed page is a register (counts) — ADT / admissions / transfers are out.
- Full clinical documentation in the chart (SOAP, nursing charts) — read-only chart stays.
- Rostering / HR beyond team postings.

## Owner decisions
- Which page is the hospital admin's landing (#11).
- Whether doctors get a web lab/referrals entry on the sidebar (#13).
- `AppointmentService::checkIn` is no longer called by the web desk; it was NOT
  deleted (other callers may exist) — removing it is an owner call.

## Shared-file changes for the lead

**#10 — `resources/views/partials/sidebars/facility_executive.blade.php`**, after the
catalogue `@endcapability` (the route already admits the CEO):
```diff
     @endcapability
+    @capability('queue')
+    <a href="{{ route('portals.queue.index') }}" class="sidebar-link {{ request()->routeIs('portals.queue.*') ? 'active' : '' }}">
+        <i data-lucide="list-ordered"></i><span>{{ __('queue_desk.nav', [], $l) }}</span>
+    </a>
+    @endcapability
 </div>
```
(or, if the CEO should NOT see the queue, remove `facility_ceo` from the `queue`
capability in `config/staff_capabilities.php` — owner choice.)

**Commit coupling:** `app/Services/Portal/DeskFacilityContext.php` is untracked and
is now referenced by `PrescriberController.php` (another agent's dirty edit) as
well as Queue/Triage/Chart controllers — it must ship in the same commit as any
of them or production fatals.

**`tests/Feature/Security/StaffAppointmentBookScopeTest.php:122-129`** (not in this
auditor's test dirs) pins the OLD behaviour, where check-in turned a foreign-facility
404 into a 302. Check-in now returns 404, the same as no-show. Suggested diff:
```diff
-        // check-in wraps its work in try/catch and reports the miss as a flash
-        // (302 back); no-show has no such wrapper and 404s. Either way nothing
+        // check-in and no-show both 404 on another facility's row. Nothing
         // at the other facility changes and no visit is opened there.
         $this->asReceptionist()
             ->post(route('portals.staff.appointments.check-in', $this->theirsAppt->id))
-            ->assertRedirect();
+            ->assertNotFound();
```
