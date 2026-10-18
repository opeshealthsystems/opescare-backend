<?php

// Registered from AppServiceProvider. The missing-documents pass:
// docs/specs/documents/20-33. Every document is rendered on demand and never
// stored (App\Services\Documents\NewDocumentRenderer).

use App\Http\Controllers\Documents\NewFacilityDocumentController as Fac;
use App\Http\Controllers\Documents\NewPatientDocumentController as Pat;
use Illuminate\Support\Facades\Route;

/*
| Patient side — the portal preamble of routes/web_family.php plus
| guardian.context, so a guardian prints for the dependent they switched to.
*/
Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'platform.admin', 'facility.context', 'patient.profile', 'guardian.profile', 'throttle:portal', 'guardian.context', 'throttle:portal.qr'])->group(function () {
    Route::get('/patient/health-passport.pdf', [Pat::class, 'passport'])->name('documents.new.passport');
    Route::get('/patient/emergency-summary.pdf', [Pat::class, 'emergency'])->name('documents.new.emergency');
    Route::get('/patient/consent-record.pdf', [Pat::class, 'consents'])->name('documents.new.consents');
    Route::get('/patient/access-log.pdf', [Pat::class, 'accessLog'])->name('documents.new.access_log');
    Route::get('/patient/appointments/{id}/slip.pdf', [Pat::class, 'appointment'])->whereUuid('id')->name('documents.new.appointment');
});

/*
| Facility side — the desk preamble; facility from DeskFacilityContext only.
*/
Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access'])->group(function () {
    Route::middleware('capability:clinical_chart')->group(function () {
        Route::get('/facility/referrals/{id}/letter.pdf', [Fac::class, 'referral'])->whereUuid('id')->name('documents.new.referral');
        Route::get('/facility/encounters/{id}/report.pdf', [Fac::class, 'encounter'])->whereUuid('id')->name('documents.new.encounter');
    });

    Route::get('/facility/lab/orders/{id}/request.pdf', [Fac::class, 'labRequest'])
        ->whereUuid('id')->middleware('capability:clinical_chart,lab_results')->name('documents.new.lab_request');

    Route::get('/facility/imaging/orders/{id}/request.pdf', [Fac::class, 'imagingRequest'])
        ->whereUuid('id')->middleware('capability:imaging')->name('documents.new.imaging_request');

    Route::middleware('capability:billing')->group(function () {
        Route::get('/facility/billing/{id}/invoice.pdf', [Fac::class, 'invoice'])->whereUuid('id')->name('documents.new.invoice');
        Route::get('/facility/billing/receipts/{id}/receipt.pdf', [Fac::class, 'receipt'])->whereUuid('id')->name('documents.new.receipt');
    });

    Route::get('/pharmacy/prescriptions/{id}/labels.pdf', [Fac::class, 'labels'])
        ->whereUuid('id')->middleware('capability:dispense')->name('documents.new.labels');

    Route::get('/facility/blood-requests/{id}/slip.pdf', [Fac::class, 'bloodRequest'])
        ->whereUuid('id')->middleware('capability:blood_inventory')->name('documents.new.blood_request');
});
