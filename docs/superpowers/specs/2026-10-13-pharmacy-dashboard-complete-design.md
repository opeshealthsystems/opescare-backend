# Pharmacy dashboard — complete desk (design, 2026-10-13)

Goal: a pharmacist, pharmacy manager or stock clerk can run the whole pharmacy from
the web desk with **no page reachable only by URL, no list without a detail page, no
action without a confirmation and a result, no empty state without guidance**. EN/FR
throughout, Lucide only, brand `#0F4C81`, XAF, nonce CSP (no inline handlers),
facility from the portal context, stock provenance rules untouched.

Yardstick: `docs/DASHBOARD_SCOPE.md`. Everything below is "run my pharmacy", nothing is
platform ops.

## Roles (config/staff_capabilities.php)
| Role | Sees |
|---|---|
| pharmacist (`dispense`, `pharmacy_stock`) | counter, prescriptions, orders, stock, controlled, reports |
| pharmacy_manager (+ facility admin caps) | all of the above + settings, team, catalogue, listing |
| medicine_stock (`pharmacy_stock`) | stock, batches, receipts, adjustments, reorder, suppliers, controlled, reports — **no** prescriptions/orders |

## Information architecture (sidebar, in order)
1. Dashboard `/pharmacy`
2. Counter lookup `/pharmacy/prescriptions/lookup`
3. Prescriptions `/pharmacy/prescriptions` → **detail** `/pharmacy/prescriptions/{id}`
4. Dispensing register `/pharmacy/dispensing` → detail = the prescription detail
5. Orders `/pharmacy/orders` → detail `/pharmacy/orders/{id}` (exists)
6. Stock `/pharmacy/stock` → **item detail** `/pharmacy/stock/items/{item}`
   - Batches `/pharmacy/stock/batches` (row → item detail, batch anchor)
   - Goods receipts `/pharmacy/stock/receipts` → `/new`, `/{id}` (exist)
   - Adjustments `/pharmacy/stock/adjustments` → **detail** `/pharmacy/stock/adjustments/{id}`
   - Reorder `/pharmacy/stock/reorder` (rules + suggested orders)
   - **Suppliers** `/pharmacy/stock/suppliers` → `/{id}` (currently POST-only)
7. Controlled substances `/pharmacy/controlled` → **register export** `/pharmacy/controlled/register?month=`
8. Price list `/pharmacy/prices` → history (exists)
9. **Reports** `/pharmacy/reports` (expiry, stock valuation, dispensing volume, low stock;
   each with CSV + PDF via the secure sheet)
10. Settings `/pharmacy/settings` (manager), Team, Catalogue, Listing (manager; facility routes)
11. Messages, Tasks, Support (staff routes)

## Pages and flows

### Dashboard
KPIs: pending scripts, dispensed today, orders awaiting action, low stock, out of stock,
expiring ≤30 days, controlled entries awaiting witness. **Every KPI links** to the
filtered list behind it. Panels: pending prescriptions (5, → detail), stock alerts (5,
→ item detail), orders needing action (5), expiring batches (5). Empty state per panel
with the next action ("Receive a delivery", "Open counter lookup").

### Prescription detail `/pharmacy/prescriptions/{id}`
Facility-scoped: shown if the script is routed to this pharmacy OR was dispensed here;
otherwise 404 (same rule as `PharmacyDeskService::find`). Sections: patient (name,
Health ID, age band — never DOB), prescriber + facility, status timeline (issued →
routed → accepted → ready → dispensed/partially dispensed/cancelled), items table (drug,
dose, qty, dispensed qty, batch chosen, stock on hand, controlled badge), dispense form
(select items, batch per item from usable stock, notes; POST existing `dispense`
route), dispense history (who, when, which items, batch), interaction alerts if any,
print-slip link (existing secure sheet). Actions respect capability `dispense`.

### Dispensing register `/pharmacy/dispensing`
Paginated log of dispense events at this facility: date, prescription ref, patient
(masked name + Health ID), items, pharmacist, controlled flag. Filters: date range,
pharmacist, controlled only. CSV export. Row → prescription detail.

### Stock item detail `/pharmacy/stock/items/{item}`
Header: medicine, ATC label, finder status (published / not, with reason), price
(→ price history). Tabs: Batches (usable / expired / expiring, per-location), Movements
(receipts, adjustments, dispensing, reservations — one unified list from
`stock_movements`), Reorder rule (edit inline), Suppliers who delivered it. Actions:
adjust (modal), receive (prefilled receipt), set reorder rule.

### Adjustment detail `/pharmacy/stock/adjustments/{id}`
Who, when, item, batch, before/after, reason (decoded), evidence note. Read-only.

### Suppliers `/pharmacy/stock/suppliers`, `/{id}`
List with contact, last delivery, open reorders; create/edit form (existing POST +
new update); detail: deliveries (receipts) and items supplied.

### Controlled register export
`/pharmacy/controlled/register?month=YYYY-MM` renders the month's register (dispense
+ witness + reconciliation entries) as the secure-sheet PDF with document QR; CSV too.

### Reports `/pharmacy/reports`
One page, four cards each with a table preview and CSV/PDF buttons: expiring stock (30/
60/90), stock valuation (qty × last price, XAF), dispensing volume by day/pharmacist,
low/zero stock vs reorder rules. Nothing invented: empty tables stay empty.

## Cross-cutting
- Every new page: route + sidebar entry (for the roles that may see it) + breadcrumb +
  `route:list` presence + a feature test (200 for the right role, 403 for the wrong
  one, 404 cross-facility).
- All strings in `lang/{en,fr}/pharmacy_desk.php` (new file; keep existing keys where
  they live). `php scripts/i18n-audit.php` must pass.
- No inline `on*=`; use `data-*` + `op-actions.js`; inline `<script @cspNonce>` only.
- No new inline `style=`; use the portal classes (`panel`, `stat-card`, `btn btn--…`).
- Controllers stay thin; logic in `app/Services/Pharmacy/*`. `PharmacyPortalController`
  (582 lines) is not grown: new pages get their own controllers.

## Out of scope
Inventory ops beyond this desk (platform), insurance, subscriptions, e-prescribing
network changes.
