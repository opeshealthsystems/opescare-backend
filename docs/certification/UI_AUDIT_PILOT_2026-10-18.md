# UI audit — pilot roles, 2026-10-18

Read-only crawl of the local dev app (`http://127.0.0.1:8041`, working tree as of this evening, other agents editing in parallel). Each role signed in through the one-click demo panel; the doctor, nurse and lab technician went through `/select-facility` (first facility offered). The audit fetched every sidebar link plus the in-page links one level down, deduplicated by route pattern. It then re-fetched every nav page with locale `fr` and checked for leftover English, untranslated `namespace.key` text, emoji, purple, stack traces and near-blank pages. Headless Chrome rendered every nav page at 390x844 in French to check for horizontal overflow and broken images.

**Coverage:** 10 demo accounts (hospital admin, facility admin, doctor, nurse, receptionist, pharmacist, lab technician, imaging-centre admin, patient, guardian), 188 page visits and 66 in-page links, with 149 pages rendered at 390px.

**Headline:** no 5xx, stack trace or untranslated key on any page reached from a sidebar. French is complete on every sidebar page; the only English left in FR is seeded *data* (drug names, message bodies). Walkthrough findings 1 and 2 are already fixed: the hospital admin's home page now reads "<Hospital> — Facility admin", and "Verify a Health ID" is in the admin sidebar and on the onboarding checklist. What remains is a small set of real defects and a larger set of demo-data hygiene problems.

Caveats: the dev server is single-threaded and slow (about 3 s per page), so the PDF timeouts below should be re-checked on prod before anyone treats them as code bugs. The "Facility admin" demo button (`demo.facility.admin@`) lands in the same tenant as the hospital admin (Hôpital Central de Yaoundé), so its table repeats the hospital admin's.

## Prioritised fix list

### P0 — blocks the Tuesday pilot
| # | Issue | Where | Suggested fix |
|---|---|---|---|
| P0-1 | **The guardian role is a dead end.** `demo.guardian@` is redirected by `RequireCompleteGuardianProfile` to `/portals/guardian/complete-profile` on every page: no dashboard, no dependents, no sidebar. The user has `patient_id = NULL`, `profile_completed_at = NULL` and no family link in the local DB. | `app/Http/Middleware/RequireCompleteGuardianProfile.php`; demo seeders (the guardian's FamilyLink) | Fix or re-run the demo family seeding so the demo guardian has an active link, then confirm on prod that the guardian demo opens `/patient/dependents`. |
| P0-2 | **Test fixtures show up as patients in the clinician demo.** The doctor's dashboard queue lists "Expiry Proof" (`CM-CONSENT-0002`), "Consent Loop" (`CM-CONSENT-0001`), `CM-NOREL-0001` and `CM-RXVERIFY-001`, and the lab dashboard shows a test called **"NOT MINE - Liver Function"**. A hospital will read this as a broken product. | dev DB, facility "Afamba"; `/facility`, `/facility/queue`, `/facility/lab/dashboard` | Purge the leftover test rows (a suite was probably pointed at the dev DB) or re-seed. Check that prod demo data is clean before Tuesday. |

### P1 — embarrassing in front of hospitals
| # | Issue | Where | Suggested fix |
|---|---|---|---|
| P1-1 | All four pharmacy report PDFs return 500: "Maximum execution time of 30 seconds exceeded" in a dompdf view. | `/pharmacy/reports/{expiry,valuation,dispensing,low_stock}/pdf` | Profile the render (fonts / secure-sheet layout); cap the rows. Re-test on prod first. |
| P1-2 | The lab technician lands on the generic `/facility` "Staff Clinical Portal", whose KPI cards and "View all" link to `/facility/queue`, and that returns **403** for this role. | `resources/views/portals/staff/index.blade.php:78,163,225`; `StaffPortalController@index` | Send `labtech` to `/facility/lab/dashboard`, or hide the queue cards when the user lacks `capability:queue`. |
| P1-3 | The lab dashboard overflows at 390px (scrollWidth 587px) because the `.od-tab` row and `.od-table` don't scroll inside their own container. | `/facility/lab/dashboard`; `ui-audit-2026-10-18/Lab_technician_facility_lab_dashboard.png` | Wrap the tabs and table in `overflow-x:auto`. |
| P1-4 | `/facility/messages` overflows at 390px (406px; `.od-list__meta` and kebab menu). | `ui-audit-2026-10-18/Doctor_facility_messages.png` | Add `min-width:0` and ellipsis on the list meta. |
| P1-5 | "My Team" shows raw handles like `demo.hospital.admin`, `demo.imaging.tech` where names belong (they read as code). | `/facility/team` (hospital and imaging) | Show the display name first and the email as secondary text, or fix the seeded names. |
| P1-6 | `/signup/organization` still offers "Insurance Company" (with insurer copy about claims/preauthorisation) and an "Insurance billing" checkbox. Out of scope per CLAUDE.md and DASHBOARD_SCOPE; walkthrough finding 3 is still open. | `lang/{en,fr}/onboarding.php` and the organization signup view | Remove the insurer type and the billing option, or get the owner to confirm. |
| P1-7 | The doctor and nurse queue rows show the age band as raw `adult` / `child`, untranslated in FR ("CM-… · adult"). | `/facility` queue widget, `/facility/queue` | Render through `__('chart.age_bands.*')` as `portals/chart/show.blade.php` already does. |

### P2 — later
| # | Issue | Where | Suggested fix |
|---|---|---|---|
| P2-1 | The pharmacist sidebar carries procurement: Suppliers ("what is still on order"), Goods receipts, Reorder levels. DASHBOARD_SCOPE lists procurement/supplier/PO as NOT in scope. | `/pharmacy/stock/{suppliers,receipts,reorder}` | Owner decision: keep it as a "simple reorder note" or drop it. |
| P2-2 | The hospital admin home KPIs use platform wording: "Jetons d'accès actifs", "Total des identifiants de santé", "Événements de sécurité". | `/admin` (facility admin home) | Replace with facility numbers (team, today's queue, Health IDs verified here). |
| P2-3 | The 403 page doesn't name the reason (scope rule) and offers "Se connecter" to a signed-in user. | `resources/views/errors/403.blade.php` | Name the missing role/capability and offer "Back to my dashboard". |
| P2-4 | The 404 page renders `lang="en"` even after `/lang/fr`. | errors/404 and locale middleware on fallback | Apply the session locale on fallback/error rendering. |
| P2-5 | Routes not in any sidebar: `/pharmacy/settings` (403 for the pharmacist), `/facility/imaging/reports` (403 for the lab technician; radiology reporting is NOT in scope). `/new`, `/create` and `/check-in` routes are reached by in-page buttons, which is fine. | `routes/*` | Wire them for the right role or remove them; confirm imaging/reports is a register, not reporting. |
| P2-6 | The patient home has an `<img>` with an empty `src` (resolves to `/patient`), so it counts as a broken image. It isn't visible on screen. | `/patient`; `Patient_patient.png` | Don't render the `<img>` when there is no photo URL. |
| P2-7 | `/pharmacy`, `/pharmacy/stock` and `/pharmacy/prescriptions` all have the same `<title>` "Pharmacy Portal". | pharmacy layout | Give each page its own title. |
| P2-8 | Clinician sidebar link `/select-facility` renders the public shell (links to marketing `/` and `/contact`). | `resources/views/auth/select_facility.blade.php` | Use the portal shell when signed in. |
| P2-9 | The guardian form calls its free-text field "Justification clinique de la demande d'accès", which is clinical wording for a family member. | `/portals/guardian/complete-profile` | Rename to "Why you need access". |

### Checked and clean
- Emoji: none. Purple: none in rendered markup/CSS. Broken images: only P2-6.
- 5xx / stack traces: none on any nav page; only the PDFs in P1-1.
- 390px: 147 of 149 nav pages pass (exceptions P1-3, P1-4).
- EN/FR: every nav page title and sidebar is translated; English left in FR is seeded data (drug names, messages, care-plan text, "Medical Council of Cameroon" as an issuing body).

## Per-role tables
Status `302→200` means a redirect was followed. "(in-page …)" rows are links found inside page bodies. "untranslated keys: demo.*" on My Team is P1-5 (email handles), not missing lang keys.

### Hospital admin (demo.hospital.admin@opescare.test) — lands on `/admin`

| Page (title) | Route | Status | Issues |
|---|---|---|---|
| Hôpital Central de Yaoundé — Facility admin | `/admin` | 200 | ok |
| Facility overview — OpesCare | `/facility/team/overview` | 200 | ok |
| Invoices — OpesCare | `/facility/team/overview/invoices` | 200 | ok |
| Departments — OpesCare | `/facility/team/overview/departments` | 200 | ok |
| Reports — OpesCare | `/facility/team/overview/reports` | 200 | ok |
| Wards & beds — OpesCare | `/facility/team/overview/wards` | 200 | ok |
| My hospital | `/facility/settings` | 200 | ok |
| My Team — OpesCare | `/facility/team` | 200 | untranslated keys: demo.facility.admin, demo.facility.ceo, demo.hospital.admin |
| Health ID Verification — OpesCare | `/verify/health-id` | 200 | ok |
| Our services — OpesCare | `/facility/catalogue` | 200 | ok |
| Patient queue | `/facility/queue` | 200 | ok |
| My tasks — OpesCare | `/facility/tasks` | 200 | ok |
| Help & support — OpesCare | `/facility/support` | 200 | ok |
| My Profile | `/professional/profile` | 200 | ok |
| Join a facility — OpesCare | `/professional/facilities` | 200 | ok |
| Hôpital Central de Yaoundé — Facility admin | `/facility` (in-page) | 302→200 | ok |
| One Health ID, connected care across Cameroon — OpesCare | `/` (in-page) | 200 | ok |
| Help Center — OpesCare | `/help` (in-page) | 200 | ok |
| Contact — OpesCare | `/contact` (in-page) | 200 | ok |
| (in-page from /facility/team/overview/departments) | `/facility/team/members/00000000-0000-0000-0000-200000000011` | 200 | ok |
| (in-page from /facility/catalogue) | `/facility/catalogue/browse` | 200 | ok |
| (in-page from /facility/queue) | `/facility/queue/check-in` | 200 | ok |

### Facility admin (demo.facility.admin@opescare.test) — lands on `/admin`

| Page (title) | Route | Status | Issues |
|---|---|---|---|
| Hôpital Central de Yaoundé — Facility admin | `/admin` | 200 | ok |
| Facility overview — OpesCare | `/facility/team/overview` | 200 | ok |
| Invoices — OpesCare | `/facility/team/overview/invoices` | 200 | ok |
| Departments — OpesCare | `/facility/team/overview/departments` | 200 | ok |
| Reports — OpesCare | `/facility/team/overview/reports` | 200 | ok |
| Wards & beds — OpesCare | `/facility/team/overview/wards` | 200 | ok |
| My hospital | `/facility/settings` | 200 | ok |
| My Team — OpesCare | `/facility/team` | 200 | untranslated keys: demo.facility.admin, demo.facility.ceo, demo.hospital.admin |
| Health ID Verification — OpesCare | `/verify/health-id` | 200 | ok |
| Our services — OpesCare | `/facility/catalogue` | 200 | ok |
| Patient queue | `/facility/queue` | 200 | ok |
| My tasks — OpesCare | `/facility/tasks` | 200 | ok |
| Help & support — OpesCare | `/facility/support` | 200 | ok |
| My Profile | `/professional/profile` | 200 | ok |
| Join a facility — OpesCare | `/professional/facilities` | 200 | ok |
| Hôpital Central de Yaoundé — Facility admin | `/facility` (in-page) | 302→200 | ok |
| One Health ID, connected care across Cameroon — OpesCare | `/` (in-page) | 200 | ok |
| Help Center — OpesCare | `/help` (in-page) | 200 | ok |
| Contact — OpesCare | `/contact` (in-page) | 200 | ok |

### Doctor (demo.doctor@opescare.test) — lands on `/facility`

| Page (title) | Route | Status | Issues |
|---|---|---|---|
| Staff Clinical Portal | `/facility` | 200 | ok |
| Global Search | `/facility/search` | 200 | ok |
| Open a patient chart | `/facility/patients` | 200 | ok |
| Referrals | `/facility/referrals` | 200 | ok |
| Triage | `/facility/triage` | 200 | ok |
| Patient queue | `/facility/queue` | 200 | ok |
| Appointments | `/facility/appointments` | 200 | ok |
| Waitlist | `/facility/appointments/waitlist` | 200 | ok |
| Prescriptions | `/facility/prescriptions` | 200 | ok |
| Certificates and sick notes | `/facility/certificates` | 200 | ok |
| Blood Bank Inventory | `/facility/inventory/blood` | 200 | ok |
| Blood Requests | `/facility/blood-requests` | 200 | ok |
| Messages — OpesCare | `/facility/messages` | 200 | overflows 390px (406px) |
| My tasks — OpesCare | `/facility/tasks` | 200 | ok |
| Help & support — OpesCare | `/facility/support` | 200 | ok |
| Choose your active facility — OpesCare | `/select-facility` | 200 | ok |
| My Profile | `/professional/profile` | 200 | ok |
| Join a facility — OpesCare | `/professional/facilities` | 200 | ok |
| One Health ID, connected care across Cameroon — OpesCare | `/` (in-page) | 200 | ok |
| Contact — OpesCare | `/contact` (in-page) | 200 | ok |
| (in-page from /facility) | `/facility/appointments/create` | 200 | ok |
| (in-page from /facility) | `/facility/queue/check-in` | 200 | ok |
| (in-page from /facility) | `/facility/triage/new` | 200 | ok |
| (in-page from /facility) | `/facility/prescriptions/new` | 200 | ok |
| (in-page from /facility) | `/facility/referrals/new` | 200 | ok |
| (in-page from /facility) | `/facility/queue/01a14e6d-5d47-7016-907c-3ec7acc41a8e` | 200 | ok |
| (in-page from /facility/referrals) | `/facility/referrals/01a14e84-2ee5-70a6-981a-ef1d32731595` | 200 | ok |
| (in-page from /facility/triage) | `/facility/triage/c5afd9e1-06b6-40d5-9827-0e0a7c47fe75` | 200 | ok |
| (in-page from /facility/prescriptions) | `/facility/prescriptions/01a06e3d-4b5d-73d8-ac34-92964a319822` | 200 | ok |
| (in-page from /facility/certificates) | `/facility/certificates/new` | 200 | ok |
| (in-page from /facility/blood-requests) | `/facility/blood-requests/new` | 200 | ok |
| (in-page from /facility/messages) | `/facility/messages/76b37c47-1bad-4504-909a-6d88a6f50b40` | 200 | ok |

### Nurse (demo.nurse@opescare.test) — lands on `/facility`

| Page (title) | Route | Status | Issues |
|---|---|---|---|
| Staff Clinical Portal | `/facility` | 200 | ok |
| Global Search | `/facility/search` | 200 | ok |
| Open a patient chart | `/facility/patients` | 200 | ok |
| Referrals | `/facility/referrals` | 200 | ok |
| Triage | `/facility/triage` | 200 | ok |
| Patient queue | `/facility/queue` | 200 | ok |
| Appointments | `/facility/appointments` | 200 | ok |
| Waitlist | `/facility/appointments/waitlist` | 200 | ok |
| Blood Bank Inventory | `/facility/inventory/blood` | 200 | ok |
| Blood Requests | `/facility/blood-requests` | 200 | ok |
| Messages — OpesCare | `/facility/messages` | 200 | ok |
| My tasks — OpesCare | `/facility/tasks` | 200 | ok |
| Help & support — OpesCare | `/facility/support` | 200 | ok |
| Choose your active facility — OpesCare | `/select-facility` | 200 | ok |
| My Profile | `/professional/profile` | 200 | ok |
| Join a facility — OpesCare | `/professional/facilities` | 200 | ok |
| One Health ID, connected care across Cameroon — OpesCare | `/` (in-page) | 200 | ok |
| Contact — OpesCare | `/contact` (in-page) | 200 | ok |
| (in-page from /facility/referrals) | `/facility/referrals/new` | 200 | ok |
| (in-page from /facility/referrals) | `/facility/referrals/01a14e84-2ee5-70a6-981a-ef1d32731595` | 200 | ok |
| (in-page from /facility/triage) | `/facility/triage/new` | 200 | ok |
| (in-page from /facility/triage) | `/facility/triage/c5afd9e1-06b6-40d5-9827-0e0a7c47fe75` | 200 | ok |
| (in-page from /facility/queue) | `/facility/queue/check-in` | 200 | ok |
| (in-page from /facility/queue) | `/facility/queue/01a14e6d-5d47-7016-907c-3ec7acc41a8e` | 200 | ok |
| (in-page from /facility/appointments/waitlist) | `/facility/appointments/create` | 200 | ok |
| (in-page from /facility/blood-requests) | `/facility/blood-requests/new` | 200 | ok |
| (in-page from /facility/messages) | `/facility/messages/76b37c47-1bad-4504-909a-6d88a6f50b40` | 200 | ok |

### Receptionist (demo.receptionist@opescare.test) — lands on `/facility`

| Page (title) | Route | Status | Issues |
|---|---|---|---|
| Staff Clinical Portal | `/facility` | 200 | ok |
| Global Search | `/facility/search` | 200 | ok |
| Patient queue | `/facility/queue` | 200 | ok |
| Appointments | `/facility/appointments` | 200 | ok |
| Waitlist | `/facility/appointments/waitlist` | 200 | ok |
| Blood Bank Inventory | `/facility/inventory/blood` | 200 | ok |
| Blood Requests | `/facility/blood-requests` | 200 | ok |
| Messages — OpesCare | `/facility/messages` | 200 | ok |
| Billing | `/facility/billing` | 200 | ok |
| Cashier session | `/facility/billing/sessions` | 200 | ok |
| Refunds | `/facility/billing/refunds` | 200 | ok |
| My tasks — OpesCare | `/facility/tasks` | 200 | ok |
| Help & support — OpesCare | `/facility/support` | 200 | ok |
| My Profile | `/professional/profile` | 200 | ok |
| Join a facility — OpesCare | `/professional/facilities` | 200 | ok |
| (in-page from /facility) | `/facility/appointments/create` | 200 | ok |
| (in-page from /facility) | `/facility/queue/check-in` | 200 | ok |
| (in-page from /facility/blood-requests) | `/facility/blood-requests/96bcace0-4db3-4831-b38f-b06cc6575ad6` | 200 | ok |
| (in-page from /facility/billing) | `/facility/billing/new` | 200 | ok |
| (in-page from /facility/billing) | `/facility/billing/01a14e7f-4a7e-7151-b5e6-bde137f44f98` | 200 | ok |

### Pharmacist (demo.pharmacist@opescare.test) — lands on `/pharmacy/stock`

| Page (title) | Route | Status | Issues |
|---|---|---|---|
| Pharmacy Portal — OpesCare | `/pharmacy/stock` | 200 | ok |
| Pharmacy Portal — OpesCare | `/pharmacy` | 200 | ok |
| Pharmacy Portal — OpesCare | `/pharmacy/prescriptions` | 200 | ok |
| Dispense at the counter | `/pharmacy/prescriptions/lookup` | 200 | ok |
| Dispensing register | `/pharmacy/dispensing` | 200 | ok |
| Controlled substances | `/pharmacy/controlled` | 200 | ok |
| Orders | `/pharmacy/orders` | 200 | ok |
| Price list | `/pharmacy/prices` | 200 | ok |
| Messages — OpesCare | `/facility/messages` | 200 | ok |
| Batches & expiry | `/pharmacy/stock/batches` | 200 | ok |
| Movement log | `/pharmacy/stock/adjustments` | 200 | ok |
| Reorder levels | `/pharmacy/stock/reorder` | 200 | ok |
| Suppliers | `/pharmacy/stock/suppliers` | 200 | ok |
| Reports | `/pharmacy/reports` | 200 | ok |
| Goods receipts | `/pharmacy/stock/receipts` | 200 | ok |
| My tasks — OpesCare | `/facility/tasks` | 200 | ok |
| Help & support — OpesCare | `/facility/support` | 200 | ok |
| My Profile | `/professional/profile` | 200 | ok |
| Join a facility — OpesCare | `/professional/facilities` | 200 | ok |
| Pharmacy Portal — OpesCare | `/pharmacy/stock?stock_status=in_stock` | 200 | ok |
| Pharmacy Portal — OpesCare | `/pharmacy/stock?stock_status=low_stock` | 200 | ok |
| Pharmacy Portal — OpesCare | `/pharmacy/stock?stock_status=out_of_stock` | 200 | ok |
| Pharmacy Portal — OpesCare | `/pharmacy/stock?stock_status=unknown` | 200 | ok |
| Pharmacy Portal — OpesCare | `/pharmacy/stock?page=2` | 200 | ok |
| Pharmacy Portal — OpesCare | `/pharmacy/stock?page=3` | 200 | ok |
| Pharmacy Portal — OpesCare | `/pharmacy/stock?page=4` | 200 | ok |
| Pharmacy Portal — OpesCare | `/pharmacy/stock?page=5` | 200 | ok |
| Pharmacy Portal — OpesCare | `/pharmacy/stock?page=6` | 200 | ok |
| Staff Clinical Portal | `/facility` (in-page) | 200 | ok |
| (in-page from /pharmacy/stock) | `/pharmacy/stock/receipts/new` | 200 | ok |
| (in-page from /pharmacy/stock) | `/pharmacy/stock/items/01a14cc2-df48-7159-b78a-708f45c2a2e2` | 200 | ok |
| (in-page from /pharmacy/stock) | `/pharmacy/prices/01a14cc2-df48-7159-b78a-708f45c2a2e2/history` | 200 | ok |
| (in-page from /pharmacy) | `/pharmacy/prescriptions/01a14cb2-963e-70c2-9ffa-7c1040ba9b18` | 200 | ok |
| (in-page from /pharmacy/stock/adjustments) | `/pharmacy/stock/adjustments/01a14cc2-f6ac-7131-b47e-21ecc00fd9f7` | 200 | ok |
| (in-page from /pharmacy/stock/suppliers) | `/pharmacy/stock/suppliers/01a14cc2-b829-704b-b747-93fffeb520a3` | 200 | ok |
| (in-page from /pharmacy/reports) | `/pharmacy/reports/expiry/csv` | 200 | ok |
| (in-page from /pharmacy/reports) | `/pharmacy/reports/expiry/pdf` | 500 | error text |
| (in-page from /pharmacy/reports) | `/pharmacy/reports/valuation/csv` | 200 | ok |
| (in-page from /pharmacy/reports) | `/pharmacy/reports/valuation/pdf` | 500 | error text |
| (in-page from /pharmacy/reports) | `/pharmacy/reports/dispensing/csv` | 200 | ok |
| (in-page from /pharmacy/reports) | `/pharmacy/reports/dispensing/pdf` | 500 | error text |
| (in-page from /pharmacy/reports) | `/pharmacy/reports/low_stock/csv` | 200 | ok |
| (in-page from /pharmacy/reports) | `/pharmacy/reports/low_stock/pdf` | 500 | error text |
| (in-page from /pharmacy/stock/receipts) | `/pharmacy/stock/receipts/01a14cc2-df18-72b6-9a35-0f2f1912e1fe` | 200 | ok |

### Lab technician (demo.labtech@opescare.test) — lands on `/facility`

| Page (title) | Route | Status | Issues |
|---|---|---|---|
| Staff Clinical Portal | `/facility` | 200 | ok |
| Global Search | `/facility/search` | 200 | ok |
| Laboratory dashboard | `/facility/lab/dashboard` | 200 | overflows 390px (587px) |
| Patient test requests | `/facility/lab/requests` | 200 | ok |
| Laboratory desk | `/facility/lab` | 200 | ok |
| Sample collection | `/facility/lab/collection` | 200 | ok |
| Imaging desk | `/facility/imaging` | 200 | ok |
| Blood Bank Inventory | `/facility/inventory/blood` | 200 | ok |
| Blood Requests | `/facility/blood-requests` | 200 | ok |
| Messages — OpesCare | `/facility/messages` | 200 | ok |
| My tasks — OpesCare | `/facility/tasks` | 200 | ok |
| Help & support — OpesCare | `/facility/support` | 200 | ok |
| Choose your active facility — OpesCare | `/select-facility` | 200 | ok |
| My Profile | `/professional/profile` | 200 | ok |
| Join a facility — OpesCare | `/professional/facilities` | 200 | ok |
| One Health ID, connected care across Cameroon — OpesCare | `/` (in-page) | 200 | ok |
| Contact — OpesCare | `/contact` (in-page) | 200 | ok |
| (in-page from /facility) | `/facility/queue` | 403 | dead link |
| (in-page from /facility/lab/dashboard) | `/facility/lab/orders/01a06edd-a4f2-7129-a728-450b2282cc4b` | 200 | ok |
| (in-page from /facility/lab/dashboard) | `/network/lab-test-finder` | 200 | ok |
| (in-page from /facility/imaging) | `/facility/imaging/orders/new` | 200 | ok |
| (in-page from /facility/blood-requests) | `/facility/blood-requests/new` | 200 | ok |
| (in-page from /facility/messages) | `/facility/messages/76b37c47-1bad-4504-909a-6d88a6f50b40` | 200 | ok |

### Imaging (demo.imaging.admin@opescare.test) — lands on `/admin`

| Page (title) | Route | Status | Issues |
|---|---|---|---|
| Centre d'Imagerie Demo Bonanjo — Facility admin | `/admin` | 200 | ok |
| Facility overview — OpesCare | `/facility/team/overview` | 200 | ok |
| Invoices — OpesCare | `/facility/team/overview/invoices` | 200 | ok |
| Reports — OpesCare | `/facility/team/overview/reports` | 200 | ok |
| Diagnostic centre | `/facility/diagnostic` | 200 | ok |
| My imaging centre | `/facility/settings` | 200 | ok |
| My Team — OpesCare | `/facility/team` | 200 | untranslated keys: demo.imaging.admin, demo.imaging.tech |
| Health ID Verification — OpesCare | `/verify/health-id` | 200 | ok |
| Our services — OpesCare | `/facility/catalogue` | 200 | ok |
| Patient queue | `/facility/queue` | 200 | ok |
| My tasks — OpesCare | `/facility/tasks` | 200 | ok |
| Help & support — OpesCare | `/facility/support` | 200 | ok |
| My Profile | `/professional/profile` | 200 | ok |
| Join a facility — OpesCare | `/professional/facilities` | 200 | ok |
| Centre d'Imagerie Demo Bonanjo — Facility admin | `/facility` (in-page) | 302→200 | ok |
| One Health ID, connected care across Cameroon — OpesCare | `/` (in-page) | 200 | ok |
| Help Center — OpesCare | `/help` (in-page) | 200 | ok |
| Contact — OpesCare | `/contact` (in-page) | 200 | ok |
| (in-page from /facility/diagnostic) | `/facility/imaging` | 200 | ok |
| (in-page from /facility/diagnostic) | `/facility/imaging/orders/new` | 200 | ok |
| (in-page from /facility/diagnostic) | `/network/lab-test-finder` | 200 | ok |
| (in-page from /facility/diagnostic) | `/network/imaging-finder` | 200 | ok |
| (in-page from /facility/team) | `/facility/team/members/01a14cbf-b223-7129-8686-df5fedb83a79` | 200 | ok |
| (in-page from /facility/catalogue) | `/facility/catalogue/browse` | 200 | ok |
| (in-page from /facility/queue) | `/facility/queue/check-in` | 200 | ok |

### Patient (demo.patient@opescare.test) — lands on `/patient`

| Page (title) | Route | Status | Issues |
|---|---|---|---|
| Health ID — OpesCare Patient Portal | `/patient` | 200 | broken image |
| Appointments — OpesCare Patient Portal | `/patient/appointments` | 200 | ok |
| Appointment Waitlist — OpesCare Patient Portal | `/patient/waitlist` | 200 | ok |
| My Prescriptions | `/patient/prescriptions` | 200 | ok |
| My Family | `/patient/family` | 200 | ok |
| Messages — OpesCare | `/patient/messages` | 200 | ok |
| Immunisations — OpesCare | `/patient/immunizations` | 200 | ok |
| Health timeline — OpesCare | `/patient/timeline` | 200 | ok |
| Documents — OpesCare | `/patient/documents` | 200 | ok |
| Allergies — OpesCare | `/patient/allergies` | 200 | ok |
| Conditions — OpesCare | `/patient/clinical-history` | 200 | ok |
| Care plans — OpesCare | `/patient/care-plans` | 200 | ok |
| Referrals — OpesCare Patient Portal | `/patient/referrals` | 200 | ok |
| Access logs — OpesCare | `/patient/access-logs` | 200 | ok |
| My dependents — OpesCare | `/patient/dependents` | 200 | ok |
| Invitations for me — OpesCare | `/patient/family/invitations` | 200 | ok |
| Notifications — OpesCare | `/patient/notifications` | 200 | ok |
| Refer & Earn — OpesCare | `/portals/patient/refer` | 200 | ok |
| Settings & Notifications — OpesCare | `/patient/settings` | 200 | ok |
| My Profile — OpesCare Patient Portal | `/patient/profile` | 200 | ok |
| Care Map — Health Facility Directory — OpesCare | `/care-map` | 200 | ok |
| One Health ID, connected care across Cameroon — OpesCare | `/` (in-page) | 200 | ok |
| Emergency Care Access — OpesCare | `/care-map/emergency` (in-page) | 200 | ok |
| Care Map — Health Facility Directory — OpesCare | `/care-map?facility_type=clinic` (in-page) | 200 | ok |
| Care Map — Health Facility Directory — OpesCare | `/care-map?facility_type=pharmacy` (in-page) | 200 | ok |
| Care Map — Health Facility Directory — OpesCare | `/care-map?facility_type=hospital` (in-page) | 200 | ok |
| Care Map — Health Facility Directory — OpesCare | `/care-map?facility_type=health_center` (in-page) | 200 | ok |
| Care Map — Health Facility Directory — OpesCare | `/care-map?facility_type=laboratory` (in-page) | 200 | ok |
| Care Map — Health Facility Directory — OpesCare | `/care-map?facility_type=dental` (in-page) | 200 | ok |
| Care Map — Health Facility Directory — OpesCare | `/care-map?facility_type=imaging_center` (in-page) | 200 | ok |
| (in-page from /patient) | `/patient/appointments/book` | 200 | ok |
| (in-page from /patient/appointments) | `/patient/appointments/00000000-0000-0000-0a01-000000000001/reschedule` | 200 | ok |
| (in-page from /patient/family) | `/patient/family/add` | 200 | ok |
| (in-page from /patient/family) | `/patient/family/invite` | 200 | ok |
| (in-page from /patient/messages) | `/patient/messages/compose` | 200 | ok |
| (in-page from /patient/messages) | `/patient/messages/76b37c47-1bad-4504-909a-6d88a6f50b40` | 200 | ok |
| (in-page from /patient/notifications) | `/patient/notifications/preferences` | 200 | ok |
| (in-page from /care-map) | `/care-map/facility/82adfdc5-70c5-4fe5-84fa-61cd0c4f8d2c` | 200 | ok |

### Guardian (demo.guardian@opescare.test) — lands on `/portals/guardian/complete-profile`

| Page (title) | Route | Status | Issues |
|---|---|---|---|
| Complete your caregiver request / OpesCare — OpesCare | `/portals/guardian/complete-profile` | 200 | ok |
| One Health ID, connected care across Cameroon — OpesCare | `/` (in-page) | 200 | ok |
| Contact — OpesCare | `/contact` (in-page) | 200 | ok |
