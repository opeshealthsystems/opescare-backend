# Facility dashboards — readiness by type (2026-09-15)

Measured against `docs/DASHBOARD_SCOPE.md` (the Finder is not an HIS/EMR).
Detail per type: `docs/audits/<TYPE>_DASHBOARD_AUDIT_2.md`. Demo logins:
`docs/DEMO_ACCOUNTS.md`. "Ready" = every in-scope daily workflow for that type
completes on the web, with tests; remaining rows are decisions or out of scope,
not broken pages.

| Type | Ready for daily use | Fixed in the audit pass | Still open (in scope) | Owner decisions |
|---|---|---|---|---|
| Hospital | Yes | confirm could reopen completed/cancelled visits; web check-in issued no queue ticket; cross-facility check-in leaked raw error; queue/triage/chart resolve facility like every page; book paginated | admin landing page choice; waitlist caps at 200 | which page is the admin landing; doctors' lab link |
| Clinic | Yes | prescriber desk acted for "first facility in the table"; RX code shown to patients; patient notified on prescription; web referral desk | referral answer to a patient's own request (mobile only) | clinic_admin who is also the doctor |
| Health centre | Mostly | centres can publish vaccination/ANC/malaria services to the Finder; web immunisation desk; web referral up | vaccination days as structured data | nurse/midwife prescribing per national protocol; nurse-recorded malaria RDT |
| Laboratory | Yes | critical-value alerts could never be closed (now read-back to a named person); public lab-test finder; publication panel; first-run state; imaging exams off the lab finder | lab roles still land on the generic desk | `lab_validator` invitable; orders from other facilities (schema); lab roles' blood links |
| Pharmacy | Yes | web dispense bypassed routing and patient notice; stand-alone pharmacy saw empty Rx queue; collected orders never left stock; expired tile ≠ batches page; unlinked banner | batch detail page; unbounded adjustments form | whole francs on `/pharmacy/stock` (changes a test) |
| Dental | Mostly | "dentist"/"dentiste" found nothing; published prices now on the public profile; dental visit type | mobile search ignores type words | `dentist` role; mistyped "Cabinet Dentaire" listings |
| Imaging | Partly | public imaging-exam finder | no way for an imaging request to arrive; no way to attach a PDF report to the patient | radiographer role; imaging request desk over `imaging_orders`; ICHI projection |
| Eye clinic | Partly | eye words find eye clinics (opticians excluded) | eye procedures in ICHI never published (LOINC only) | basic eye exams absent from the dictionary (import); blood links for specialists; 2 eye centres typed diagnostic |
| Diagnostic centre | Mostly | catalogue shows which finder each item reaches; stale concurrent edits refused | lab dashboard counts imaging as lab; settings page has no stale-edit check | packages/bundles (two tables); imaging landing |
| Maternity | Mostly | newborn could not get a Health ID on the day of birth; French maternity words | antenatal/postnatal visit presets | midwife creating a newborn Health ID at the desk |

## Cross-cutting (all types)
- **Blocks email everywhere (owner):** null MX at Hostinger — staff invites, lead-conversion invites and notifications by email cannot leave.
- **Refusal style:** admin-tier roles opening a clinical desk get a redirect, not a named 403 (encoded deliberately in the role matrix; a design call).
- **Public profile** never shows the facility photo (the upload exists; no image slot on the page).
- **Directory search** is not paginated in SQL.
