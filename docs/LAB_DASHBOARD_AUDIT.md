# Laboratory dashboard — production-readiness audit (2026-09-14)

Scope: the portal a laboratory logs into (`/facility`, roles `lab_manager`,
`labtech`, `lab_scientist`, `lab_validator`, `sample_collection`; sidebars
`partials/sidebars/{lab_manager,labtech,sample_collection}.blade.php`). Every
claim below is checked against the code with file:line evidence. Nothing is
inferred from a sidebar alone. Same shape as `PHARMACY_DASHBOARD_AUDIT.md`.

## Verdict

**The bench is production-grade: worklist, sample collection, result entry
with derived flags from the one published-range authority, critical alerts,
release, second-person validation and append-only corrections all exist and
are tested.** What a laboratory MANAGER needs around the bench — a landing
page that says how the lab is doing, the requests patients send the lab, a
way to hand a walk-in patient a printed report, and a way to refuse a bad
sample — either lives only in the mobile API or does not exist. The landing
page a lab role sees today is the generic staff dashboard (appointments,
queue, referrals, invoices) and says nothing about the laboratory.

## What works (verified in code, real data paths)

| Page | Route | Evidence |
|---|---|---|
| Worklist (5 columns, exact Health-ID narrowing, urgency order) | `/facility/lab` | `routes/lab.php:56`, `LabDeskWebController@index:45`, `LabDeskWebService::worklist:104` |
| Sample collection | `/facility/lab/collection` + POST `collect` | `routes/lab.php:48-52`; delegated to `LabDeskService::collect:236` |
| Order page: results, flags, alert state, entry form | `/facility/lab/orders/{id}` | `LabDeskWebController@show:103`, `LabDeskWebService::detail:185` |
| Result entry — a NEW row every time, flag derived via `ResultFlagResolver` | POST `results` | `LabDeskWebService::writeRow:492`, `flag_source` stored (line 523) |
| Correction — new row naming the superseded one, validated order drops back to `resulted` | POST `results/{id}/correct` | `LabDeskWebService::correct:303`, `:535-538` |
| Critical value → `critical_value_alerts` row, pending until the clinician acknowledges | on entry | `LabDeskWebService::raiseAlert:561` |
| Release → patient bell + push + guardians + ordering clinician bell | POST `release` | `LabDeskService::release:314` → `notifyReleased:696-765` |
| Second-person validation (`verified`), self-validation refused | POST `validate` | `LabDeskWebService::validate:336-376` |
| Service catalogue (tests offered + `price_xaf`) | `/facility/catalogue` | `FacilityCatalogueController`, `capability:service_catalogue` held by `lab_manager` (`config/staff_capabilities.php:138`) |
| Team | `/facility/team` | `facility_team` += `lab_manager` (`config/staff_capabilities.php:127`) |
| Listing claim/edit, messaging | `/portals/listing*`, `/facility/messages` | sidebar `lab_manager.blade.php:53-75` |
| No lab route is gated on payment | — | `LabDeskTest::test_no_lab_route_is_gated_on_payment` |

## (a) What a laboratory manager CANNOT do

### L-1 — The landing page says nothing about the laboratory
`/facility` → `StaffPortalController@index` (`app/Http/Controllers/MedicalId/StaffPortalController.php:27-82`)
computes four KPIs: today's appointments, in-queue tickets, draft referrals,
issued invoices. **No lab figure at all** — not pending samples, not orders
in progress, not critical values waiting, not turnaround. The mobile app's
lab home (`LabDeskService::home:111-147`) has six honest counts, so the
numbers exist; the web has no page for them. **Turnaround time: NOT FOUND
anywhere** (no reader of `resulted_at - collected_at` in `app/`).

### L-2 — Patient test requests have NO web page
Patients send a laboratory a test request from the app
(`lab_test_requests`, model `LabTestRequest`). The lab approves it into a
real `LabOrder` or declines it with a reason — **only through the mobile
API**: `routes/mobile_lab_desk.php:52-54` → `LabRequestController@requests/
approve/decline` → `LabDeskService::approveRequest:402` / `declineRequest:445`
(which also notify the patient). `routes/lab.php` has no `requests` route;
no sidebar links one. A laboratory without the staff app never sees a
request. Note the mobile home counts them (`requests_pending`,
`LabDeskService::home:128`) so the data is live.

### L-3 — Lab orders from OTHER facilities (sample referral): NOT FOUND
`lab_orders.facility_id` is immutable (`LabOrder::IMMUTABLE_ATTRIBUTES:47-55`)
and there is no referring/performing split, no "send this sample to lab X"
action, no inbound queue. `ReferralCase` is patient referral, not specimen
referral. Not built; not a small change (needs a schema decision).

### L-4 — Printable result report: exists for the PATIENT, not for the lab
`documents/laboratory-report.blade.php` renders on the OpesCare Secure sheet
with a verification QR (`PatientDocumentService::render:106-142`). Its only
callers are the patient portal, the patient mobile app and the family routes
(`PatientPortalController`, `MobilePatientDocumentController`,
`MobileLabController`, `routes/web_family.php`). **The bench has no route to
print a released report** for a walk-in patient without a phone. Also:
`PatientDocumentService::available:166` and `labOrder:356` list/allow
`['resulted','completed','released','reported']` — **a VALIDATED order
(`status = verified`) vanishes from the patient's documents list and 404s on
download**, because the web desk's validation step post-dates that list.

### L-5 — Sample rejection: NOT FOUND
`LabOrder::TERMINAL_STATUSES` includes `rejected` (`LabOrder.php:67`) and
nothing writes it: grep `reject` in `app/Services/Lab/` → no match. A
haemolysed / mislabelled / insufficient sample cannot be refused with a
reason; the collector's only move is "Mark collected".

### L-6 — Result delivery to the patient: EXISTS at release
`LabDeskService::notifyReleased:696-729` writes the patient's bell row
(`notifications`), pushes when `PatientPush` exists, and tells guardians once.
It fires on **release** (`resulted`), not on **validation** (`verified`) —
which is correct: the patient already has the numbers; validation is a
second signature. Nothing to build.

### L-7 — Public test menu with prices: the public finder reads a DEAD table
The public lab finder is `GET /api/v1/labs/test-search` (`routes/api.php:286`)
→ `CareMapController@searchTests:180` → `LabTestSearchService::searchTests`
(`app/Modules/CareMap/Services/LabTestSearchService.php:14`) →
**`lab_test_availabilities`**. Writers of that table in `app/`, `database/`,
`routes/`: **NOT FOUND** (only `TerminologyApplier:380` updates codes on
existing rows). `facility_catalogue_items` — the thing the lab manager edits
with `price_xaf` — is read by `FacilityCatalogueController` and
`BillingDeskService` only. **The catalogue is never published to the public
finder**; the finder searches a table nothing fills. Same shape as pharmacy
P-1 (a dead table behind a live screen). Note also
`LabTestSearchService:14` is a `LIKE %term%` on test names — not a patient
search, so not a prohibition, but unbounded.

### L-8 — Reagent / consumable stock: NOT FOUND
No lab equivalent of `/pharmacy/stock`. `InventoryItem`/`InventoryCategory`
mention consumables in a comment only; no lab route, no lab sidebar link, no
service. Not built.

## (b) Navigation

| Sidebar link | Resolves? | Evidence |
|---|---|---|
| `lab_manager.blade.php:29` `portals.lab.index` | yes | `routes/lab.php:56` |
| `:34` `portals.lab.collection` | yes | `routes/lab.php:48` |
| `:43,49` blood bank / blood requests (`@capability('blood_inventory')`) | yes; gated | lab roles do not hold `blood_inventory` → renders nothing |
| `:54` messages, `:66/71` listing, `:80` catalogue, `:92` team | yes | routes in `web.php` / capability files |
| `labtech.blade.php`, `sample_collection.blade.php` | same subset | no dead link |

Controller method → view: `LabDeskWebController` `index/collection/show` →
`portals/lab/{index,collection,show}.blade.php` all exist. **URL-only
routes: none** in `routes/lab.php` (every GET is linked; POSTs are forms on
the pages). `StaffRoleMatrixTest::SURFACES:310-311` pins `lab_results` and
`sample_collection` to their pages.

## (c) Placeholder text, hardcoded English, `facility_id` from input

- **Placeholder text:** none in the three lab views. `collection.blade.php:79`
  uses a `placeholder=` attribute with a translated string — fine.
- **Hardcoded English:** `LabDeskWebService::collect:270` appends
  `'Specimen: ' . $specimenType` to `lab_orders.notes` (a stored clinical
  note, deliberately not localised — noted, not a defect). The `LogicException`
  messages in `LabOrder::booted` are developer-facing. Views: every string
  goes through `__('lab_desk.*')`; `lang/en/lab_desk.php` ↔ `lang/fr/lab_desk.php` 1:1.
- **`facility_id` from input:** none. `LabDeskWebController::facilityId:217`
  reads `PortalContextService::facilityId()` only and aborts 403 when absent;
  every service read is narrowed by the id the controller hands in
  (`LabDeskWebService::base:584`, `LabDeskService::findOrder:193`).
  Cross-facility order → 404 (`LabDeskTest::test_an_order_at_another_facility_does_not_exist_for_this_desk`).

## Gaps, ranked (what a lab needs day-to-day)

1. **L-1 landing dashboard** — the manager opens the portal and learns nothing.
2. **L-2 patient requests desk** — live inbound demand the web cannot answer.
3. **L-4 printable report from the bench + validated orders missing from the patient's list** — the walk-in patient leaves with nothing; the validated panel disappears.
4. **L-5 sample rejection** — small, safety-relevant.
5. L-7 publish the catalogue to the public finder (needs a projection like pharmacy `syncFinder()`; decide whether `lab_test_availabilities` is the projection or is replaced).
6. L-3 specimen referral (schema decision), L-8 reagent stock (medium).

## Status (2026-09-14, same day)

| Gap | Status | Where |
|---|---|---|
| L-1 landing dashboard | **built** | `/facility/lab/dashboard` — bench counts, released/validated today, awaiting validation, critical values unacknowledged (listed, linked), median + mean turnaround over 30 days (`collected_at → resulted_at`, computed in SQL, "not enough data" when < 1 released order), pending patient requests. `routes/lab_manager.php`, `LabManagerDeskController@dashboard`, `LabManagerDeskService` |
| L-2 patient requests desk | **built** | `/facility/lab/requests` (+ approve / decline with reason) through the SAME `LabDeskService::approveRequest/declineRequest` the mobile desk uses — the patient is told either way; cross-facility request → 404 |
| L-4 printable report | **built** | `GET /facility/lab/orders/{id}/report` streams the same Secure-sheet PDF the patient gets (verification row issued by the bench user); only a released or validated order; button on the order page. `PatientDocumentService` now accepts `verified` (`LabOrder::RELEASED_STATUSES`) in `available()` and `labOrder()` |
| L-5 sample rejection | **built** | `POST /facility/lab/orders/{id}/reject` — only before any result exists; reason required and appended to `notes` (the one free-text column); status → `rejected` (terminal, model-guarded); ordering clinician and patient are told; form on the order page |
| L-3 specimen referral | open | schema decision |
| L-7 catalogue → public finder | open | `lab_test_availabilities` has no writer |
| L-8 reagent stock | open | not built |
| Sidebar links for the four new pages | **pending lead** | snippets in the build report; sidebars are out of this agent's scope |

## Build order (recommendation)
1. L-1 + L-2 + L-4 + L-5 (done above)
2. L-7 catalogue projection (medium; the one that puts labs on the public map)
3. L-8 reagent stock (medium)
4. L-3 specimen referral (large)
