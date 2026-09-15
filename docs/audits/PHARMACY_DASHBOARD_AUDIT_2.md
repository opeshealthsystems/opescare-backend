# Pharmacy dashboard — second pass: the seams (2026-09-15)

Yardstick: `docs/DASHBOARD_SCOPE.md`. First pass: `docs/PHARMACY_DASHBOARD_AUDIT.md`
(P-1..P-5, P-8 live; P-6 live via the admin leads desk). This pass audits the
joins between the four slices built yesterday (stock ops, orders, prices,
settings) plus the counter, for `pharmacy_manager`, `pharmacist`,
`medicine_stock` at Pharmacy ESSEC. Prod checked GET-only (cookie jar, form
login); fixes below are local and uncommitted.

## PLAN (ranked) and status

| # | Finding | Severity | Status |
|---|---|---|---|
| PH2-1 | Web dispense called `PrescriptionService::dispense` directly: filled a script **routed to another pharmacy** (mobile desk 404s it), never claimed the script / closed `pharmacy_routing_status`, never notified the patient, and could not record a partial fill | P0 safety/consistency | **FIXED** — goes through `PharmacyDeskService::dispense`; optional `item_ids[]` checkboxes on the lookup page for partial hand-over |
| PH2-2 | `/pharmacy/prescriptions` worklist and dashboard `pending_rx` / `dispensed_today` scoped by the **issuing** facility only — a stand-alone pharmacy's routed scripts never appeared, "dispensed today" was always 0 | P1 | **FIXED** — issuing facility OR `pharmacy_route_id` in this facility's routes |
| PH2-3 | Receipt → batch → finder row → reservation → **collected/delivered did not decrement anything**; finder kept promising handed-over packs | P1 (in scope: reservations "fulfil/close" + honest availability) | **FIXED** — FEFO `dispensed` movements referencing the reservation (`reference_type=medicine_reservation`), finder republished; all-or-nothing, never blocks the hand-over; desk warns + order page shows "not recorded — record a movement" when no ledger/too few packs |
| PH2-4 | Dashboard "expired" tile used its own query (counted lots with no medicine, used the raw arithmetic) vs batches page `expired()` | P2 | **FIXED** — `PharmacyStockOpsService::expiredCount()` shared |
| PH2-5 | Orders desk for a pharmacy with no linked listing: silent empty list | P2 lying empty state | **FIXED** — warning banner (prices already had one; settings already explains) |
| PH2-6 | Order page "Prescription" printed `prescription_number` (column does not exist) → always the raw UUID; RX code (C-2) never shown | P2 | **FIXED** — shows `reference` |
| PH2-7 | New-order notification deep link went to the orders list, not the order | P3 | **FIXED** — `orders.show` |
| PH2-8 | Dashboard never mentioned patient orders (orders desk slice not joined to landing page) | P2 | **FIXED** — "Orders: N waiting" button (dispense roles only) |
| PH2-9 | `/pharmacy/stock` `unit_price` validates `numeric` with 2 decimals (existing test posts 615.50); price list and receipts are whole-franc integers | P2 inconsistency | **OPEN — owner decision** (changing it breaks `PharmacyPortalStockReportTest` expectations; recommend `integer`) |
| PH2-10 | Reservation `unit_price` snapshot comes from the public row; `total_price` rounds to 2 dp | P3 | open, follows PH2-9 |
| PH2-11 | Prescription dispense does not touch the stock ledger | — | **OUT/owner**: `prescription_items.quantity` is units (e.g. 21 capsules), ledger counts packs; mapping would invent data |
| PH2-12 | Patient notifications from the desk use app paths (`/pharmacy`, `/prescriptions`) as `action_url`; on web `/pharmacy` is the staff portal | P3 | open — mobile contract, not changed |
| PH2-13 | `/pharmacy/stock/adjustments` loads **every** batch for the movement form (unbounded `get()`); `receipts/new` loads every open purchase order | P3 | open (POs are out of scope — list only) |
| PH2-14 | No batch detail page, no supplier list/detail page (suppliers only creatable inline on receipt form) | P3 | open — batch detail in scope (small); supplier directory borderline procurement → out |
| PH2-15 | Lookup/prescriptions/dashboard views carry `__('…') ?: 'English'` fallbacks (dead code, `__()` never falsy) | cosmetic | open |

## Seams walked end to end

1. **Goods receipt → batch → price → finder → reservation → orders → collected → stock.**
   Receipt writes batch + movement and `syncFinder()` (✔). Price list writes
   `selling_price_xaf` + append-only change, `syncFinder` carries it (✔).
   Patient reserves from the public row, price snapshotted (✔). Orders desk
   transitions via the shared state machine (✔). **Collected → stock: was
   missing (PH2-3), now FEFO ledger movement + finder republish.** A pharmacy
   that only reports `/pharmacy/stock` (no ledger) gets an explicit "not
   recorded" note — packs_available is not guessed.
2. **RX code lookup → dispense → partially dispensed → ledger.** Lookup by RX
   code/Health ID across facilities (✔). Dispense bypassed routing (PH2-1,
   fixed). Partial was API-only (fixed). Ledger: out (PH2-11).
3. **Three price surfaces.** stock page `unit_price` decimal(…,2) (PH2-9);
   `inventory_items.selling_price_xaf` int; reservation `unit_price` snapshot.
   Displays all use `number_format(…, 0)` FCFA.
4. **Expired tile vs batches page** — aligned (PH2-4).

## Pages and prod status (GET, 2026-09-15)

| Page | manager | pharmacist | medicine_stock | On sidebar for |
|---|---|---|---|---|
| /pharmacy | 200 | 200 | 200 | all |
| /pharmacy/orders (+?tab=done) | 200 | 200 | 403 | manager, pharmacist |
| /pharmacy/prices | 200 | 200 | 200 | all |
| /pharmacy/settings | 200 | 403 | 403 | manager |
| /pharmacy/stock | 200 | 200 | 200 | all |
| /pharmacy/stock/{batches,adjustments,reorder,receipts,receipts/new} | 200 | 200 | 200 | all |
| /pharmacy/prescriptions, /lookup | 200 | 200 | 403 | manager, pharmacist |
| /pharmacy/controlled | 200 | 200 | 200 | all |
| /facility/team, /overview, /facility/catalogue | 200 | 403 | 403 | manager |
| /facility/messages | 200 | 200 | 403 | manager, pharmacist |
| /portals/listing, /claims | 200 | 200 | 200 | manager |

Every 403 is a page **not** on that role's sidebar (by-URL refusal naming
`role_forbidden`); every sidebar link resolves 200 — no dead links. Dashboard
counter links are wrapped in `@capability('dispense')`, so medicine_stock is
never offered a 403. Views on disk: all present
(`resources/views/portals/pharmacy/**`). Detail pages: order ✔, receipt ✔,
price history ✔; batch ✗, supplier ✗ (PH2-14).

## Security / data
- `facility_id`: session/`PortalContextService` only in all five controllers; none from input. ✔
- Cross-facility (tested locally): another pharmacy's order 404; routed-elsewhere script now 404 on web dispense; routed scripts not visible on a stranger's worklist (tests). ✔
- No patient LIKE (worklist search = exact Health ID). ✔
- Ledgers append-only: reservation stock-out uses `adjust()` → new `stock_movements` rows only. ✔
- Care never gated on payment: hand-over always stands even when the ledger can't record it. ✔
- EN/FR: new keys in `pharmacy_orders`, `pharmacy_ops`; `i18n-audit` ALL FILES PASS.

## Out of scope (DASHBOARD_SCOPE)
Purchase orders, supplier contracts, supplier directory management, cost
accounting/GL on receipts — reported, not built.
