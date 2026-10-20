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

// ── Public lab-test finder ─────────────────────────────────────────────────
// The web page over GET /api/v1/care-map/labs/test-search (same service, same
// provenance gates). Beside /network/medicine-finder and /network/blood-finder
// in routes/web.php, declared here so it does not touch that shared file.
Route::get('/network/lab-test-finder', \App\Http\Controllers\CareMap\LabTestFinderController::class)
    ->name('public.network.lab-test-finder');

// ── Patient "Refer & Earn" growth page ─────────────────────────────────────
// Distinct from the clinical referrals feature (portals.patient.referrals).
// Wrapped inline in the patient-portal middleware stack so it does not need to
// live in the sealed routes/web.php. ('web' is already applied to this file.)
Route::middleware(['auth', 'mfa.verified', 'portal.access', 'platform.admin', 'facility.context', 'guardian.context'])
    ->group(function () {
        Route::get('/portals/patient/refer', [ReferController::class, 'index'])
            ->name('portals.patient.refer');
    });

// ── Solutions: the OPES health systems catalogue ──────────────────────────
// Index, one page per system in config/solutions.php, and the buy / free
// (donation) request form that files a lead with source 'solutions'. The
// slug constraint keeps /solutions/{slug} off the older audience pages
// (/solutions/hospitals etc.) in routes/web.php.
Route::get('/solutions', [\App\Http\Controllers\Marketing\SolutionsController::class, 'index'])->name('public.solutions.index');
Route::get('/solutions/request', [\App\Http\Controllers\Marketing\SolutionsController::class, 'requestForm'])->name('public.solutions.request');
Route::post('/solutions/request', [\App\Http\Controllers\Marketing\SolutionsController::class, 'store'])->middleware('throttle:signup')->name('public.solutions.request.store');
Route::get('/solutions/{slug}', [\App\Http\Controllers\Marketing\SolutionsController::class, 'show'])
    ->whereIn('slug', array_keys((array) config('solutions.systems', [])))
    ->name('public.solutions.show');

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
        Route::get('/admin/leads/{lead}', [LeadAdminController::class, 'show'])
            ->name('portals.admin.leads.show');
        // Lead → tenant conversion (P-6). Under /admin/* so RequirePlatformAdmin
        // holds it to the platform tier like the rest of the god-mode console.
        Route::get('/admin/leads/{lead}/convert', [LeadAdminController::class, 'convertForm'])
            ->name('portals.admin.leads.convert');
        Route::post('/admin/leads/{lead}/convert', [LeadAdminController::class, 'convert'])
            ->name('portals.admin.leads.convert.store');
    });
