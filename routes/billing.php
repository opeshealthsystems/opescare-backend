<?php

use App\Http\Controllers\MedicalId\BillingDeskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Billing / cashier desk — web portal routes
|--------------------------------------------------------------------------
|
| `invoices`, `invoice_items`, `payments`, `receipts`, `refunds` and
| `cashier_sessions` shipped in 2026-05 with models and migrations; the
| `billing` capability was granted to seven roles and gated nothing. This
| file is that door.
|
| Loaded from AppServiceProvider::boot() beside routes/triage.php and
| routes/queue.php, not added to routes/web.php — parallel work on separate
| features cannot then collide in one file.
|
| THE GATE, in the order the stack applies it:
|
|   web, auth, mfa.verified   the ordinary portal preamble
|   portal.access             the user may open a facility portal at all
|   capability:billing        the role, AT THE ACTIVE FACILITY, may bill
|                             (config/staff_capabilities.php). A refusal is
|                             a 403 `role_forbidden` — never a 404.
|   capability:refund_approve the second signature on a refund. Held by
|                             refund_approver and finance_manager only; a
|                             cashier can ask, and cannot grant.
|
| Facility scope is a property of the DATA, not of the URL: every table here
| carries facility_id and BillingDeskController reads only rows whose
| facility_id is the active facility (PortalContextService, never the
| request). Another facility's invoice 404s.
|
| THE ONE RULE: care is never gated on payment. There is no route here that
| a clinical desk consults, and no clinical file that can name these tables
| (tests/Feature/Billing/BillingIsolationGuardTest.php).
*/

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'capability:billing'])->group(function () {

    // ── Invoices ─────────────────────────────────────────────────────────
    Route::get('/facility/billing', [BillingDeskController::class, 'index'])
        ->name('portals.billing.index');

    // `/new`, `/refunds` and `/sessions` precede `/{id}` so they are never swallowed.
    Route::get('/facility/billing/new', [BillingDeskController::class, 'create'])
        ->name('portals.billing.create');
    Route::post('/facility/billing', [BillingDeskController::class, 'store'])
        ->name('portals.billing.store');

    // ── Refunds ──────────────────────────────────────────────────────────
    Route::get('/facility/billing/refunds', [BillingDeskController::class, 'refunds'])
        ->name('portals.billing.refunds');
    Route::post('/facility/billing/payments/{paymentId}/refund', [BillingDeskController::class, 'requestRefund'])
        ->name('portals.billing.refund.request');

    Route::middleware('capability:refund_approve')->group(function () {
        Route::post('/facility/billing/refunds/{id}/approve', [BillingDeskController::class, 'approveRefund'])
            ->name('portals.billing.refund.approve');
        Route::post('/facility/billing/refunds/{id}/reject', [BillingDeskController::class, 'rejectRefund'])
            ->name('portals.billing.refund.reject');
    });

    // ── Cashier sessions ─────────────────────────────────────────────────
    Route::get('/facility/billing/sessions', [BillingDeskController::class, 'sessions'])
        ->name('portals.billing.sessions');
    Route::post('/facility/billing/sessions', [BillingDeskController::class, 'openSession'])
        ->name('portals.billing.sessions.open');
    Route::post('/facility/billing/sessions/{id}/close', [BillingDeskController::class, 'closeSession'])
        ->name('portals.billing.sessions.close');

    // ── Receipts (printable HTML) ────────────────────────────────────────
    Route::get('/facility/billing/receipts/{id}', [BillingDeskController::class, 'receipt'])
        ->name('portals.billing.receipt');

    // ── One invoice ──────────────────────────────────────────────────────
    Route::get('/facility/billing/{id}', [BillingDeskController::class, 'show'])
        ->name('portals.billing.show');
    Route::post('/facility/billing/{id}/issue', [BillingDeskController::class, 'issue'])
        ->name('portals.billing.issue');
    Route::post('/facility/billing/{id}/pay', [BillingDeskController::class, 'pay'])
        ->name('portals.billing.pay');
    Route::post('/facility/billing/{id}/sync-mobile', [BillingDeskController::class, 'syncMobile'])
        ->name('portals.billing.sync_mobile');

    // Void, never delete. There is deliberately no DELETE and no PUT/PATCH
    // anywhere in this feature: an invoice is voided with a reason, a payment
    // is reversed by a refund row, a session is closed with a count.
    Route::post('/facility/billing/{id}/void', [BillingDeskController::class, 'void'])
        ->name('portals.billing.void');
});
