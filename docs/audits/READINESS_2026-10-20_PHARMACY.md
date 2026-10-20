# Pharmacy readiness certification — 2026-10-20

Question: can a real Cameroon pharmacy be onboarded and run on OpesCare tomorrow?
Method: read-only code walk (routes → middleware → controller → service → tests) against
`docs/DASHBOARD_SCOPE.md`, verifying open items from `PHARMACY_DASHBOARD_AUDIT_2.md`.
Prod checked with anonymous GET only. No tests or DB run; marked items "unverified" where
only code/docs were read. This was a time-boxed pass, not an exhaustive one.

## Verdict: READY WITH CONDITIONS

Stock, catalogue, import, dispensing, orders and the finder are wired end to end and real
portal stock is published (`source_system='portal'` is on the live allow-list). The conditions
are about onboarding plumbing and money, not the pharmacy desk itself.

## Journey checklist

| # | Journey | Result | Evidence |
|---|---|---|---|
| 1a | Public profile / directory | PASS | `/care-map` 200, `/network/medicine-finder` 200, `/solutions/pharmacies` 200 on prod |
| 1b | Claim listing → admin approve | PASS (partial) | `routes/web.php:536-539`, `:615-617`; `FacilityClaimService::approveClaim` (`app/Modules/CareMap/Services/FacilityClaimService.php:180`) — join to a tenant only if claimant already holds exactly one posting (PH-2) |
| 1c | New pharmacy tenant + first manager | PASS | `LeadConversionService.php:35-70,159-213` creates facility + `pharmacy_manager` invite; `/invite/{token}` `routes/web.php:141` |
| 1d | Roles | PASS | `config/staff_capabilities.php:29-30` pharmacist, pharmacy_technician, pharmacy_manager, dispensing_officer; medicine_stock |
| 2a | Settings / hours | PASS | `routes/pharmacy_settings.php`, `tests/Feature/Pharmacy/PharmacySettingsTest.php` |
| 2b | Catalogue + price list | PASS | `routes/pharmacy_prices.php`, `PharmacyPriceListTest.php` |
| 2c | CSV/XLSX stock import | PASS | `routes/pharmacy_ops.php:89-107` (template, mapping, preview, commit, errors.csv), `StockImportTest.php` |
| 2d | Live stock becomes visible | PASS (condition) | Portal writes `source_system='portal'` (`PharmacyStockReportService.php:50,141`), allow-list `MedicinePharmacyStock.php:48`. `syncFinder` returns null when no linked listing (`PharmacyStockOpsService.php:606-616`) → PH-2 |
| 3a | Rx dispensing by Health ID / RX code | PASS | `routes/web.php:669-675`; web dispense via `PharmacyDeskService` (PH2-1 fixed); mobile `routes/mobile_pharmacy_desk.php:44-55` |
| 3b | Patient orders / reservations | PASS | `routes/pharmacy_orders.php`, `mobile_pharmacy_desk.php:57-63`, `PharmacyOrdersDeskTest.php` |
| 3c | Receipt, adjustment, reorder, suppliers | PASS | `routes/pharmacy_ops.php:54-121`; supplier list/detail now exist (PH2-14 partly closed) |
| 3d | Reports / registers | PASS | dispensing export `web.php:675`, `PharmacyReportsTest.php`, controlled register tests |
| 3e | Payments (MoMo / Orange) | FAIL for live money | PH-1 |
| 4 | Patient finder + reserve | PASS | `routes/api.php:256-264`, `:289`; public finder 200 |
| 5a | i18n | PASS (sampled) | `lang/en|fr/pharmacy_ops.php` both 155 lines; audit script not run here |
| 5b | Role gates | PASS | capability middleware per group (`pharmacy_ops.php:54`, `mobile_pharmacy_desk.php:35-36`); prior prod 403 matrix in audit 2 |
| 5c | Facility isolation | PASS (prior audit, not re-tested) | `PortalContextService` only; audit 2 "Security / data" |
| 5d | Tests | PASS (present, not run) | 18 files in `tests/Feature/Pharmacy` |

## Findings

| ID | Severity | Finding | Evidence | Fix |
|---|---|---|---|---|
| PH-1 | HIGH (BLOCKER if pharmacies must be paid on-platform) | No live mobile-money rail exists. Registry only has commented placeholders and falls back to the simulator, allowed in `local, testing, demo`. Prod runs `APP_ENV=demo` (memory: finder-deploy-env, unverified today), so payments on opescare.com are **simulated**, never real. | `app/Services/Payments/MobileMoneyProviderRegistry.php:58-75`; `Providers/DemoPaymentProvider.php:85` | Implement MTN MoMo / Orange providers + credentials; move prod off `APP_ENV=demo` once real data lands. Care is never gated on payment, so pharmacy ops still work |
| PH-2 | HIGH | Approving a public listing claim only links the listing to a tenant when the claimant already holds exactly one posting; a walk-in pharmacy claimant has none, so the listing stays unlinked and `syncFinder` writes nothing — the pharmacy's stock never reaches the finder until an admin links by hand. | `FacilityClaimService.php:190-197, 288-298, 318-360`; `PharmacyStockOpsService.php:612-616` | Document the onboarding path as: lead → `LeadConversionService` (creates tenant + invite) → admin "link listing" (`AdminDirectoryReviewController::linkListing`). Or make claim approval offer tenant creation |
| PH-3 | MEDIUM | `/pharmacy/stock` `unit_price` accepts 2 decimals while the price list/receipts are whole francs (PH2-9 still open). | `app/Http/Controllers/MedicalId/PharmacyPortalController.php:556`; Connect API `InventoryController.php:81` | Owner decision: switch to `integer` (XAF exponent 0) and update `PharmacyPortalStockReportTest` |
| PH-4 | MEDIUM | Prescription dispense does not decrement the stock ledger (units vs packs) — finder can overstate after counter dispensing. | Audit 2 PH2-11 (not re-verified in code today) | Owner decision on unit→pack mapping (pack size per item) |
| PH-5 | LOW | Patient notifications from the desk use app paths as `action_url`; on web `/pharmacy` is the staff portal. | Audit 2 PH2-12 (unverified today) | Web-aware link resolver |
| PH-6 | LOW | Adjustment form loads every batch unbounded. | Audit 2 PH2-13 (unverified today) | Paginate / search select |
| PH-7 | LOW | Invite email delivery not verified end to end. MX now resolves (registrar eforward1-5), so the old null-MX blocker looks cleared, but no send was tested. | `nslookup -type=MX opescare.com` 2026-10-20 | Send one real invite and confirm receipt |

No BLOCKER found in the pharmacy desk itself. Not checked this pass: mobile-width rendering, CSRF on each form (Laravel `web` group default), FR copy quality, prod authenticated pages.

## Owner decisions needed
1. Payments: is on-platform collection required for launch? If yes, PH-1 is a blocker (contract + credentials with MTN/Orange).
2. Whole-franc pricing on `/pharmacy/stock` (PH-3).
3. Unit-to-pack mapping so counter dispensing moves stock (PH-4).
4. Approve the onboarding runbook: lead conversion + manual listing link (PH-2), or fund automatic tenant creation on claim approval.
