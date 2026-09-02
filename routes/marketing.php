<?php

use App\Http\Controllers\Marketing\LeadAdminController;
use App\Http\Controllers\Marketing\PricingController;
use App\Http\Controllers\Marketing\ReferController;
use App\Http\Controllers\Marketing\RequestDemoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketing / growth public routes
|--------------------------------------------------------------------------
| Loaded with the 'web' middleware group from AppServiceProvider so it does
| not collide with the sealed routes/web.php during concurrent work.
*/

Route::get('/pricing', [PricingController::class, 'index'])->name('public.pricing');

// ── Patient "Refer & Earn" growth page ─────────────────────────────────────
// Distinct from the clinical referrals feature (portals.patient.referrals).
// Wrapped inline in the patient-portal middleware stack so it does not need to
// live in the sealed routes/web.php. ('web' is already applied to this file.)
Route::middleware(['auth', 'mfa.verified', 'portal.access', 'platform.admin', 'facility.context', 'guardian.context'])
    ->group(function () {
        Route::get('/portals/patient/refer', [ReferController::class, 'index'])
            ->name('portals.patient.refer');
    });

// ── Public "Request a demo" funnel (B2B lead capture) ──────────────────────
Route::get('/request-demo',  [RequestDemoController::class, 'show'])->name('public.request-demo');
Route::post('/request-demo', [RequestDemoController::class, 'store'])->middleware('throttle:signup')->name('public.request-demo.store');

// ── Admin leads inbox ──────────────────────────────────────────────────────
// Wrapped inline in the full portal/admin middleware stack so it does not need
// to live in the sealed routes/web.php.
// ('web' is already applied to this file by AppServiceProvider, so we add only
// the auth/portal stack here to avoid re-running the web group twice.)
Route::middleware(['auth', 'mfa.verified', 'portal.access', 'platform.admin', 'facility.context'])
    ->group(function () {
        Route::get('/portals/admin/leads', [LeadAdminController::class, 'index'])
            ->name('portals.admin.leads');
        Route::post('/portals/admin/leads/{lead}/status', [LeadAdminController::class, 'updateStatus'])
            ->name('portals.admin.leads.status');
    });
