# Readiness certification — diagnostics (2026-10-20)

Scope: imaging centres, laboratories, diagnostic centres, blood banks.
Question: can a real one be onboarded and run on OpesCare tomorrow?
Method: read-only. Code read at `master` (f504b694); public GETs against https://opescare.com.
No tests, migrations or DB access were run. This is a lighter-weight audit than the
`*_DASHBOARD_AUDIT_2.md` files: it checks their claims against today's code and adds findings.
Items marked "not verified" were not walked end to end.

## Verdicts

| Type | Verdict | One line |
|---|---|---|
| Laboratory | **Ready, with conditions** | Desk, collection, result entry, critical-value read-back, manager dashboard and public lab-test finder (`/network/lab-test-finder` 200) are all present. Orders only come from the lab's own facility (DX-4). |
| Imaging centre | **Ready for walk-in only** | Intake is by exact Health ID at the centre. Worklist, report draft/finalise/amend/distribute and PDF attachment all exist. The ordering doctor is never told the report is out (DX-3), and there is no inbound referral (DX-4). |
| Diagnostic centre | **Ready, with the lab and imaging conditions above** | Both desks work. Catalogue items say which finder they reach. |
| Blood bank | **Not ready to onboard on its own** | Stock entry and blood requests exist, and live stock can reach the finder. But it has no role of its own, its administrators are locked out of stock (DX-1), and stock stays invisible until the bank is linked in the directory (DX-2). |

## Journey checklist

| Journey | Lab | Imaging | Blood |
|---|---|---|---|
| Discover / claim / directory link | yes (shared directory claim flow; not re-walked) | yes | yes, but publishing needs `care_facilities.facility_id` (DX-2) |
| Catalogue + prices (LOINC / ICHI) | yes, `LabCatalogueProjector` stamps `portal` | yes, imaging exam finder | n/a |
| Staff roles | labtech, lab_scientist, lab_manager, lab_validator, sample_collection (`config/staff_capabilities.php:24-25`) | imaging_tech, radiologist (`:28`) | **none of its own**; works through `$lab`, doctors, nurses or front desk (`:130`) |
| Worklist / intake | `/facility/lab`, `/facility/lab/collection` | `/facility/imaging`, intake by Health ID (`ImagingDeskController.php:76-99`) | `/facility/inventory/blood`, `/facility/blood-requests` |
| Result / report sign-off | `lab_results` capability | `imaging_report` = radiologist and lab_manager (`staff_capabilities.php:118`) | n/a |
| Critical values | read-back to a named person (`/facility/lab/alerts/{id}/callback`) | n/a | n/a |
| Delivery to patient | results inbox and app `labs/{id}/report` | `distribute` → `NotifyPatientOfRadiologyReport` | n/a |
| Delivery to ordering doctor | own-facility chart | **missing** (DX-3) | n/a |
| Secure PDF + QR | request.pdf and report via secure sheet | request.pdf and attachment | slip.pdf |
| Public finder | `/network/lab-test-finder` 200, allow-list `['portal']` (`LabTestAvailability.php:37`) | `/network/imaging-finder` 200 | `/network/blood-finder` and `/finder/blood` 200; allow-list `blood_inventory`, `portal`, `partner_api*` (`BloodAvailability.php:50`) |
| Facility isolation | facility comes from session; another facility's row returns 404 (`LabManagerDeskController.php:27`) | same | same, and the blood portal is middleware-resolved (`routes/web.php:346-352`) |
| Audit log | yes | yes, every patient read (`ImagingDeskController.php:91,127,139,196`) | not verified |
| EN/FR | `imaging_report` is 171/171 lines; full `i18n-audit.php` not run (no PHP on this shell) | — | — |
| Billing / TAT / DHIS2 | not re-walked; per the earlier audits, invoices and DHIS2 aggregates are shared platform features | — | — |

## Findings

| ID | Sev | Finding | Evidence |
|---|---|---|---|
| DX-1 | **BLOCKER (blood bank)** | `blood_inventory` leaves out every facility-admin role (`facility_admin`, `hospital_admin`, …). A blood bank's own administrator cannot enter or adjust stock or answer blood requests; the blood-requests desk uses the same gate. | `config/staff_capabilities.php:37,130`; `routes/web.php:348`, blood-requests block near `:895` |
| DX-2 | HIGH | Stock is published only if the tenant is linked to a directory listing. With no link, `projectFacility` returns 0 without an error, so a newly onboarded bank's stock never reaches the finder. The staff page does compute `$publishesToFinder`, but with only 468 of 482 tenants linked this needs an onboarding step and a check. | `BloodAvailabilityProjector.php:134-142`; `InventoryPortalController.php:87` |
| DX-3 | HIGH | Distributing an imaging report records only the radiologist (`[$this->actorId()]`) in `distributed_to`. The only listener notifies the patient. The ordering doctor (`ordered_by`) is never notified. | `ImagingReportController.php:146`; `RadiologyReportService.php:195-222`; `app/Listeners/NotifyPatientOfRadiologyReport.php` |
| DX-4 | HIGH | No cross-facility orders. `imaging_orders` and `lab_orders` carry one `facility_id` and no ordering or performing facility, so a clinic doctor cannot send a request to an independent lab or imaging centre. The patient has to walk in with their Health ID. This repeats the open item in FACILITY_READINESS. | `2026_05_28_100001_create_imaging_orders_table.php:14`; `2026_05_28_000001_create_patient_mobile_api_tables.php:51,96`; `grep performing_facility_id app` finds nothing |
| DX-5 | MEDIUM | There is no blood-bank role (blood_bank_officer, transfusion tech) and no blood-bank demo tenant or account. `tests/Feature/BloodBank` has one file. | `staff_capabilities.php`; `docs/DEMO_ACCOUNTS.md` |
| DX-6 | MEDIUM | DEMO_ACCOUNTS is stale: it says `demo.imaging.tech` is "labtech (no imaging role exists)", but `imaging_tech` and `radiologist` have existed since 2026-10-13. | `docs/DEMO_ACCOUNTS.md`; `staff_capabilities.php:26-28` |
| DX-7 | MEDIUM | The lab dashboard counts imaging as lab work, and lab roles still land on the generic desk. Both were open in the earlier audits and were not re-verified as fixed. | `docs/FACILITY_READINESS.md` |
| DX-8 | LOW | `/blood`, `/blood-finder` and `/laboratories` return 404 on prod. The canonical URLs are `/network/blood-finder`, `/finder/blood` and the lab-test finder. A redirect would catch guessed URLs. | curl, 2026-10-20 |
| DX-9 | LOW | Not verified here: the full i18n audit, view:cache, phone-width rendering, billing and TAT reports for diagnostic tests. | — |

Confirmed correct: the provenance allow-lists withhold seeded stock (an empty finder is correct). A real bank's stock does become visible, through the staff screen (`portal`), the facility API (`blood_inventory`) or a partner (`partner_api:*`), but only once DX-2's directory link exists.

## Owner decisions

1. **Blood-bank staffing:** add the facility-admin roles to `blood_inventory` (fixes DX-1), and decide whether to add dedicated blood-bank roles (DX-5).
2. **Cross-facility orders:** decide whether independent labs and imaging centres should receive requests from other facilities. This is a schema change (an ordering facility separate from the performing facility) plus a consent decision.
3. **Ordering-doctor delivery** for imaging reports: notify `ordered_by` when a report is distributed (DX-3).
4. **Directory link at onboarding:** decide whether onboarding should refuse to finish until the tenant is linked, or keep warning on the staff page (DX-2).
