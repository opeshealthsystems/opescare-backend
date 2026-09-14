# Pharmacy dashboard — production-readiness audit (2026-09-14)

Scope: the portal a pharmacy logs into (`/pharmacy`, roles `pharmacy_manager`,
`pharmacist`, `pharmacy_technician`, `dispensing_officer`, `medicine_stock`) to
set up its facility, stock, prices and staff. Every claim below was checked
against the code AND against finder.opescare.cloud logged in as
`demo.pharmacist@opescare.test` (Pharmacy ESSEC). Nothing here is inferred
from a sidebar alone.

## Verdict

**Stock and dispensing are production-grade. Everything around them —
getting a pharmacy onto the platform, setting it up, pricing, staff, and
patient orders — is either manual-by-admin, gated away from pharmacy roles,
or has no web page.** A pharmacy that already exists as a tenant with a
manager account can run its counter today; a pharmacy that walks up to the
site cannot get in, and even a live one cannot see the orders patients place.

Numbers from prod: the demo pharmacy has 44 public stock rows
(`medicine_pharmacy_stocks`), 0 ledger rows (`inventory_items`), 0 rows in the
legacy `pharmacy_inventories`, and **0 `pharmacy_manager` users exist on the
whole platform** — the manager desk has never been logged into.

## What works (verified 200 on prod, real data paths)

| Page | Route | Notes |
|---|---|---|
| Dashboard | `/pharmacy` | Renders; but see P-1 — three of six KPI tiles read a dead table |
| Stock (public availability) | `/pharmacy/stock` + POST | Reads/writes `medicine_pharmacy_stocks` via `PharmacyStockReportService` — this IS what the patient Medicine Finder shows |
| Stock ops: batches, receipts (+new, show), adjustments, reorder, suppliers | `/pharmacy/stock/*` | Append-only ledger on `inventory_items`/`stock_movements`; `syncFinder()` republishes availability after every ledger write ✔ |
| Prescriptions worklist + Health-ID lookup + dispense | `/pharmacy/prescriptions*` | Through `PrescriptionService`; consent-scoped |
| Controlled substances register | `/pharmacy/controlled` | |
| Messages | `/facility/messages` | |
| Opening hours + services + phones/email/website/description | `/portals/listing` (needs an approved claim) | `care_facilities` listing editor |
| Claim a listing | `/portals/listing/claims` | Claim → admin approval → `care_facilities.facility_id` link |

## Gaps, ranked

### P-1 — Dashboard KPIs read a table nothing writes (wrong numbers, silently)
`PharmacyPortalController@dashboard` (lines 64–104) computes `total_drugs`,
`low_stock`, `expired` and the alerts list from `PharmacyInventory`
(`pharmacy_inventories`). The only writer of that table is
`Modules/Inventory/Services/PharmacyInventoryService`, whose only web caller is
`InventoryPortalController`, which is routed for **blood** only. So a pharmacy
with 44 stock rows and a full batch ledger sees "0 drugs, 0 low stock,
0 expired" on its landing page, forever. **Fix:** compute from
`medicine_pharmacy_stocks` (+ `inventory_items` batches for expiry) and delete
`PharmacyInventory`/`pharmacy_inventories` or leave a note.

### P-2 — Patient orders/reservations have NO web page
`medicine_reservations` (a patient reserves a medicine from the Finder; holds
`unit_price`/`total_price`) is handled only through the **mobile** API
(`Api/Mobile/Pharmacy/PharmacyReservationController`). The web portal has no
`/pharmacy/orders` (404 on prod), no sidebar link, no accept/ready/collected
flow, no notification landing page. A pharmacy without the staff app never
sees an order. **Build:** orders desk (list by status, confirm / ready /
collected / reject with reason, all forward-only), bell + sound already exist
platform-side for `pharmacy` category events — wire the URL.

### P-3 — Staff management is unreachable for every pharmacy role
`/facility/team` (invite, reissue, revoke, approve join requests) is gated by
`capability:facility_team`, held only by `facility_admin`, `clinic_admin`,
`hospital_admin`, `facility_ceo`, `branch_admin`. A pharmacy manager is
bounced to `/pharmacy` (302 on prod); no pharmacy sidebar links it. A
pharmacy therefore cannot add its own pharmacists, technicians or stock
staff — the platform admin must. **Fix:** add `pharmacy_manager` (and
`lab_manager` for the same reason) to `facility_team`; link it in
`pharmacy_manager.blade.php`; the team page must offer only pharmacy roles
when the tenant is a pharmacy (offerable-vs-assignable rule).

### P-4 — Prices: only the public availability form carries a price; no price list
`medicine_pharmacy_stocks.unit_price` + `pack_size` is the price a patient
sees, set row-by-row on `/pharmacy/stock`. The stock-ops ledger
(`inventory_items`) has no selling price, receipts carry a cost only, and
there is no price-list page, no bulk edit, no history of price changes.
**Build:** price on the ledger item (selling price, XAF whole francs),
`syncFinder()` carries it to the public row, a `/pharmacy/prices` page
(edit per medicine, last-changed-by), price change = a `stock_movements`-style
append row.

### P-5 — Facility setup is partial and split across two objects
The listing editor edits `care_facilities` (public card) — phones, email,
website, description, hours, services. **It cannot change name, address, GPS
position, photo/logo, or facility type** (those are import-time fields), and
it is only reachable once a claim is approved (`/portals/listing` = 404 until
then). The tenant record (`facilities`) has no self-service page at all
(`/facility/settings` does not exist). **Build:** one "My pharmacy" settings
page: name/address/GPS (map picker exists: `partials/finder/map`), photo,
hours, contacts, licence number, with edits to name/address going to a review
queue (they change what the public directory says).

### P-6 — Onboarding is a lead form, not a signup
`/signup` → "Organisation" → `submitOrganizationRegister` writes a `Lead` and
emails support (which currently cannot send: null MX). There is no path from
lead → tenant → manager account except a platform admin creating everything
by hand in `/admin` (the leads desk at `portals.admin.leads` only changes a
status). **Build:** admin "Convert lead" action that creates the `facilities`
tenant, links or creates the `care_facilities` listing, creates the manager
user with `pharmacy_manager`, and sends the invite (already exists:
`portals.facility.team.invite`).

### P-7 — Service catalogue link on the manager sidebar 403s
`pharmacy_manager.blade.php` links `portals.facility.catalogue`, gated by
`capability:service_catalogue`, which pharmacy roles do not hold (403 on prod).
Either grant it (a pharmacy does pick what it stocks from the institutional
catalogue) or remove the link.

### P-8 — Nobody is a pharmacy manager
0 users hold `pharmacy_manager` on prod; the demo pharmacist is the only
pharmacy login. Add `demo.pharmacy.manager@opescare.test` to the demo seeder
so the manager desk is exercised, and so you can review it.

### P-9 — Three inventory tables
`medicine_pharmacy_stocks` (public, care_facility-keyed), `inventory_items`
(ledger, facility-keyed, new), `pharmacy_inventories` (legacy, unwritten).
Two is defensible (ledger vs projection); the third only misleads (P-1).

## Not gaps (checked)
- Stock ops → public finder projection: present (`syncFinder`, provenance
  `source_system` respected).
- `facility_id` sourcing: every pharmacy controller reads
  `PortalContextService`/session facility, none from input.
- i18n: all pharmacy views go through `__()`; audit clean.
- Cross-facility: batch/receipt lookups scoped to the session facility (404).

## Build order (recommendation)
1. P-1 KPI fix + P-7 + P-8 (small, immediate honesty)
2. P-3 staff for pharmacies (small; unlocks self-service)
3. P-2 orders desk (medium; the revenue flow)
4. P-4 prices (medium)
5. P-5 settings page (medium)
6. P-6 lead → tenant conversion (medium; needs admin UI)
