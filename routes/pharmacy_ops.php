<?php

use App\Http\Controllers\MedicalId\PharmacyStockOpsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pharmacy stock operations — web portal routes
|--------------------------------------------------------------------------
|
| The supply-chain schema (`inventory_items`, `stock_batches`,
| `stock_movements`, `goods_receipts`, `reorder_rules`, `suppliers`) shipped
| in 2026_05 and had exactly one caller — the mobile desk's pack adjustment —
| and no screen. A pharmacy manager could report public availability
| (/pharmacy/stock, the row the patient finder reads) but had nowhere to run
| the shop behind it: no lots, no expiry dates, no delivery log, no minimum
| levels. This file is that back office.
|
| Loaded from AppServiceProvider::boot() beside routes/triage.php and
| routes/queue.php rather than added to routes/web.php, so parallel work on
| separate desks cannot collide in one file. The paths deliberately sit
| UNDER /pharmacy/stock — the public-availability page — because the ledger
| feeds it: PharmacyStockOpsService::syncFinder() republishes a medicine's
| usable (unexpired) quantity there after every movement.
|
| THE GATE, in the order the stack applies it:
|
|   web, auth, mfa.verified   the ordinary portal preamble
|   capability:pharmacy_stock the role, AT THE ACTIVE FACILITY, may keep stock
|                             (config/staff_capabilities.php: the pharmacy
|                             roles plus medicine_stock — the receptionist and
|                             the lab hold it nowhere). A refusal is a 403
|                             `role_forbidden`, never a 404.
|   portal.access             the user may open the pharmacy portal at all
|
| The capability is judged BEFORE portal.access, unlike routes/triage.php,
| and on purpose: portal.access answers a wrong-bucket role (a receptionist
| under /pharmacy) with a redirect to its own portal, which would turn the
| role refusal into a 302 and hide it. Both gates still have to pass.
|
| Facility scope is a property of the DATA, not of the URL: every table here
| carries facility_id, the controller reads it from PortalContextService only,
| and a batch or receipt belonging to another pharmacy is not refused — it
| does not exist (404).
|
| There is no PUT, PATCH or DELETE anywhere in this file, on purpose. A
| movement is never edited (StockMovement refuses updates at the model); a
| wrong count is corrected by recording another movement.
*/

Route::middleware(['web', 'auth', 'mfa.verified', 'capability:pharmacy_stock', 'portal.access'])->group(function () {

    // ── Batches & expiry ─────────────────────────────────────────────────
    Route::get('/pharmacy/stock/batches', [PharmacyStockOpsController::class, 'batches'])
        ->name('portals.pharmacy.stock.batches');

    // ── Movement log (append-only) ───────────────────────────────────────
    Route::get('/pharmacy/stock/adjustments', [PharmacyStockOpsController::class, 'adjustments'])
        ->name('portals.pharmacy.stock.adjustments');
    Route::post('/pharmacy/stock/adjustments', [PharmacyStockOpsController::class, 'storeAdjustment'])
        ->name('portals.pharmacy.stock.adjustments.store');

    // ── Reorder rules ────────────────────────────────────────────────────
    Route::get('/pharmacy/stock/reorder', [PharmacyStockOpsController::class, 'reorder'])
        ->name('portals.pharmacy.stock.reorder');
    Route::post('/pharmacy/stock/reorder', [PharmacyStockOpsController::class, 'storeReorderRule'])
        ->name('portals.pharmacy.stock.reorder.store');

    // ── Goods receipts ───────────────────────────────────────────────────
    // Literal segments (`/new`) precede the `{id}` wildcard so they are never
    // swallowed by it.
    Route::get('/pharmacy/stock/receipts', [PharmacyStockOpsController::class, 'receipts'])
        ->name('portals.pharmacy.stock.receipts');
    Route::get('/pharmacy/stock/receipts/new', [PharmacyStockOpsController::class, 'createReceipt'])
        ->name('portals.pharmacy.stock.receipts.create');
    Route::post('/pharmacy/stock/receipts', [PharmacyStockOpsController::class, 'storeReceipt'])
        ->name('portals.pharmacy.stock.receipts.store');
    Route::post('/pharmacy/stock/suppliers', [PharmacyStockOpsController::class, 'storeSupplier'])
        ->name('portals.pharmacy.stock.suppliers.store');
    Route::get('/pharmacy/stock/receipts/{id}', [PharmacyStockOpsController::class, 'showReceipt'])
        ->name('portals.pharmacy.stock.receipts.show');
});
